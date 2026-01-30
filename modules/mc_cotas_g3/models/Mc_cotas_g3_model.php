<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mc_cotas_g3_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('mc_cotas_g3/sqlsrv_connector');
    }

    /**
     * Obter configuração do SQL Server
     *
     * @return array
     */
    public function get_sqlserver_config()
    {
        return [
            'host'     => get_option('mc_cotas_g3_sqlserver_host'),
            'user'     => get_option('mc_cotas_g3_sqlserver_user'),
            'password' => get_option('mc_cotas_g3_sqlserver_password'),
            'database' => get_option('mc_cotas_g3_sqlserver_database'),
            'port'     => get_option('mc_cotas_g3_sqlserver_port'),
        ];
    }

    /**
     * Testar conexão com SQL Server
     *
     * @return array
     */
    public function test_connection()
    {
        $config = $this->get_sqlserver_config();
        $connector = new Sqlsrv_connector($config);

        $success = $connector->test_connection();

        return [
            'success' => $success,
            'message' => $success ? 'Conexão estabelecida com sucesso!' : $connector->get_last_error(),
        ];
    }

    /**
     * Sincronizar membros do Multiclubes com leads EXISTENTES do MyLeads CRM
     * Nova lógica: compara telefones e atualiza apenas os que batem
     *
     * @param array $options
     * @return array
     */
    public function sync_members($options = [])
    {
        $start_time = microtime(true);

        $stats = [
            'success'              => false,
            'total_members'        => 0,
            'total_leads'          => 0,
            'total_leads_eligible' => 0,
            'matched'              => 0,
            'updated_leads'        => 0,
            'not_matched'          => 0,
            'already_synced'       => 0,
            'errors'               => 0,
            'error_log'            => [],
            'execution_time'       => 0,
            'batches'              => 0,
        ];

        try {
            // Configurar conexão
            $config = $this->get_sqlserver_config();
            $connector = new Sqlsrv_connector($config);

            if (!$connector->connect()) {
                throw new Exception('Erro ao conectar ao SQL Server: ' . $connector->get_last_error());
            }

            // Preparar filtros
            $filters = [];

            if (get_option('mc_cotas_g3_sync_only_titular') == '1') {
                $filters['only_titular'] = true;
            }

            if (get_option('mc_cotas_g3_sync_only_active') == '1') {
                $filters['only_active'] = true;
            }

            // Contar total de membros
            $total_members = $connector->count_members($filters);

            if ($total_members === false) {
                throw new Exception('Erro ao contar membros: ' . $connector->get_last_error());
            }

            $stats['total_members'] = $total_members;

            // Buscar todos os leads com telefone do MyLeads CRM
            log_activity('MC Cotas G3 - Carregando leads do MyLeads...');

            // Total de leads com telefone (para estatística)
            $total_with_phone = $this->db->where('phonenumber IS NOT NULL')
                                         ->where('phonenumber !=', '')
                                         ->count_all_results(db_prefix() . 'leads');

            $stats['total_leads'] = $total_with_phone;

            // Buscar APENAS leads que NÃO foram sincronizados ainda (mc_member_id IS NULL)
            $leads = $this->db->select('id, name, phonenumber, description, status')
                              ->where('phonenumber IS NOT NULL')
                              ->where('phonenumber !=', '')
                              ->group_start()
                                  ->where('mc_member_id IS NULL')
                                  ->or_where('mc_member_id', '')
                                  ->or_where('mc_member_id', '0')
                              ->group_end()
                              ->get(db_prefix() . 'leads')
                              ->result_array();

            $stats['total_leads_eligible'] = count($leads);
            $stats['already_synced'] = $total_with_phone - count($leads);

            log_activity(sprintf('MC Cotas G3 - %d leads com telefone, %d elegíveis para sync, %d já sincronizados',
                $total_with_phone, count($leads), $stats['already_synced']));

            // Criar mapa de telefones -> lead_id
            $phone_map = [];
            $digits = (int)get_option('mc_cotas_g3_match_phone_digits') ?: 8;

            foreach ($leads as $lead) {
                $clean_phone = $this->clean_phone($lead['phonenumber'], $digits);
                if (!empty($clean_phone)) {
                    // Pode ter múltiplos leads com mesmo telefone, pegar o primeiro
                    if (!isset($phone_map[$clean_phone])) {
                        $phone_map[$clean_phone] = $lead;
                    }
                }
            }

            log_activity(sprintf('MC Cotas G3 - %d telefones únicos mapeados', count($phone_map)));

            // Tamanho do lote (batch)
            $batch_size = (int)get_option('mc_cotas_g3_sync_batch_size') ?: 100;

            // Calcular número de lotes
            $total_batches = ceil($total_members / $batch_size);

            // Processar membros em lotes
            for ($batch = 0; $batch < $total_batches; $batch++) {
                $offset = $batch * $batch_size;

                // Buscar lote de membros
                $filters['limit'] = $batch_size;
                $filters['offset'] = $offset;

                $members = $connector->get_members($filters);

                if ($members === false) {
                    throw new Exception('Erro ao buscar membros (lote ' . ($batch + 1) . '): ' . $connector->get_last_error());
                }

                // Log do lote
                if (get_option('mc_cotas_g3_enable_detailed_log') == '1') {
                    log_activity(sprintf(
                        'MC Cotas G3 - Processando lote %d/%d (%d membros)',
                        $batch + 1,
                        $total_batches,
                        count($members)
                    ));
                }

                // Processar cada membro do lote
                foreach ($members as $member) {
                    try {
                        $result = $this->process_member_match($member, $phone_map, $digits);

                        if ($result['action'] == 'matched') {
                            $stats['matched']++;
                            $stats['updated_leads']++;
                        } elseif ($result['action'] == 'not_matched') {
                            $stats['not_matched']++;
                        }
                    } catch (Exception $e) {
                        $stats['errors']++;
                        $stats['error_log'][] = [
                            'member_id'   => $member['MemberId'] ?? 'N/A',
                            'member_name' => $member['MemberName'] ?? 'N/A',
                            'member_phone' => $member['MemberMobilePhone'] ?? 'N/A',
                            'error'       => $e->getMessage(),
                        ];

                        if (get_option('mc_cotas_g3_enable_detailed_log') == '1') {
                            log_activity('MC Cotas G3 - Erro ao processar membro ' . ($member['MemberName'] ?? 'N/A') . ': ' . $e->getMessage());
                        }
                    }
                }

                $stats['batches']++;

                // Limpar memória
                unset($members);
                gc_collect_cycles();
            }

            $stats['success'] = true;
        } catch (Exception $e) {
            $stats['error_log'][] = [
                'error' => $e->getMessage(),
            ];

            log_activity('MC Cotas G3 - Erro na sincronização: ' . $e->getMessage());
        }

        // Calcular tempo de execução
        $stats['execution_time'] = round(microtime(true) - $start_time, 2);

        // Salvar log de sincronização
        $this->save_sync_log($stats);

        return $stats;
    }

    /**
     * Limpar telefone e pegar X últimos dígitos
     *
     * @param string $phone
     * @param int $last_digits
     * @return string
     */
    private function clean_phone($phone, $last_digits = 8)
    {
        // Remover tudo que não é número
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Pegar X últimos dígitos
        if (strlen($clean) >= $last_digits) {
            return substr($clean, -$last_digits);
        }

        return $clean;
    }

    /**
     * Processar membro: comparar telefone e atualizar lead se bater
     *
     * @param array $member
     * @param array $phone_map
     * @param int $digits
     * @return array
     */
    private function process_member_match($member, $phone_map, $digits)
    {
        $member_phone = $member['MemberMobilePhone'] ?? '';

        if (empty($member_phone)) {
            return ['action' => 'not_matched', 'reason' => 'Sem telefone'];
        }

        // Limpar telefone do membro
        $clean_member_phone = $this->clean_phone($member_phone, $digits);

        if (empty($clean_member_phone)) {
            return ['action' => 'not_matched', 'reason' => 'Telefone inválido'];
        }

        // Verificar se existe lead com esse telefone
        if (!isset($phone_map[$clean_member_phone])) {
            return ['action' => 'not_matched', 'reason' => 'Telefone não encontrado nos leads'];
        }

        $lead = $phone_map[$clean_member_phone];

        // ATUALIZAR O LEAD
        $this->update_lead_with_member_data($lead['id'], $member);

        return [
            'action'  => 'matched',
            'lead_id' => $lead['id'],
            'phone'   => $clean_member_phone
        ];
    }

    /**
     * Atualizar lead com dados do membro do Multiclubes
     *
     * @param int $lead_id
     * @param array $member
     * @return void
     */
    private function update_lead_with_member_data($lead_id, $member)
    {
        // Buscar lead atual
        $lead = $this->db->where('id', $lead_id)->get(db_prefix() . 'leads')->row();

        if (!$lead) {
            throw new Exception('Lead não encontrado: ' . $lead_id);
        }

        // Preparar nova descrição (adicionar ao que já existe)
        $new_description = $this->prepare_multiclubes_description($member);

        // Combinar descrições
        $combined_description = trim($lead->description);
        if (!empty($combined_description)) {
            $combined_description .= "\n\n---\n\n";
        }
        $combined_description .= $new_description;

        // Preparar dados para atualizar
        $update_data = [
            'description'    => $combined_description,
            'mc_member_id'   => $member['MemberId'],
            'mc_title_code'  => $member['TitleCode'] ?? '',
            'mc_is_titular'  => ($member['Titular'] == 'Titular') ? 1 : 0,
            'mc_vendedor'    => $member['VendedorNome'] ?? '',
            'mc_data_venda'  => !empty($member['DataVenda']) ? date('Y-m-d H:i:s', strtotime($member['DataVenda'])) : null,
        ];

        // Atualizar status se configurado
        if (get_option('mc_cotas_g3_update_status_on_match') == '1') {
            $closed_status = $this->find_closed_status();
            if ($closed_status) {
                $update_data['status'] = $closed_status->id;
                $update_data['last_status_change'] = date('Y-m-d H:i:s');
            }
        }

        // Executar update
        $this->db->where('id', $lead_id);
        $this->db->update(db_prefix() . 'leads', $update_data);

        // Salvar/atualizar na tabela de sync
        $this->save_or_update_sync_record($member['MemberId'], $lead_id, $member);

        // Log
        if (get_option('mc_cotas_g3_enable_detailed_log') == '1') {
            log_activity(sprintf(
                'MC Cotas G3 - Lead #%d (%s) atualizado com dados do membro #%d (%s)',
                $lead_id,
                $lead->name,
                $member['MemberId'],
                $member['MemberName']
            ));
        }
    }

    /**
     * Preparar descrição do Multiclubes
     *
     * @param array $member
     * @return string
     */
    private function prepare_multiclubes_description($member)
    {
        $desc = "**INFORMAÇÕES DO MULTICLUBES**\n\n";
        $desc .= "**Status:** Cliente Ativo ✅\n";

        if (!empty($member['TitleTypeName'])) {
            $desc .= "**Plano/Título:** " . $member['TitleTypeName'];
            if (!empty($member['TitleCode'])) {
                $desc .= " (" . $member['TitleCode'] . ")";
            }
            $desc .= "\n";
        }

        if (!empty($member['Titular'])) {
            $desc .= "**Tipo:** " . $member['Titular'] . "\n";
        }

        if (!empty($member['MemberStatus'])) {
            $desc .= "**Status no Multiclubes:** " . $member['MemberStatus'] . "\n";
        }

        // VENDEDOR/CONSULTOR COM CONTATOS (IMPORTANTE!)
        $vendedor = $member['VendedorNome'] ?? '';
        if (!empty($vendedor)) {
            $desc .= "**Vendedor/Consultor:** " . $vendedor . " 👤\n";

            // Telefone do vendedor
            if (!empty($member['VendedorTelefone'])) {
                $desc .= "**Telefone do Vendedor:** " . $member['VendedorTelefone'] . "\n";
            }

            // Email do vendedor
            if (!empty($member['VendedorEmail'])) {
                $desc .= "**Email do Vendedor:** " . $member['VendedorEmail'] . "\n";
            }
        }

        if (!empty($member['DataVenda'])) {
            $desc .= "**Data da Venda:** " . date('d/m/Y', strtotime($member['DataVenda'])) . "\n";
        }

        if (!empty($member['MemberDocumentNumber'])) {
            $desc .= "**CPF:** " . $member['MemberDocumentNumber'] . "\n";
        }

        $desc .= "\n*Sincronizado em: " . date('d/m/Y H:i') . "*";

        return $desc;
    }

    /**
     * Buscar status "Customer" ou "Negócio Fechado"
     *
     * @return object|null
     */
    private function find_closed_status()
    {
        $search_name = get_option('mc_cotas_g3_closed_status_name') ?: 'Customer';

        // Buscar exato primeiro
        $status = $this->db->where('name', $search_name)
                           ->get(db_prefix() . 'leads_status')
                           ->row();

        if ($status) {
            return $status;
        }

        // Buscar similar
        $similar_names = ['Customer', 'Negócio Fechado', 'Fechado', 'Ganho', 'Cliente'];

        foreach ($similar_names as $name) {
            $status = $this->db->like('name', $name, 'both')
                               ->get(db_prefix() . 'leads_status')
                               ->row();

            if ($status) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Salvar ou atualizar registro de sincronização
     *
     * @param int $member_id
     * @param int $lead_id
     * @param array $member
     * @return void
     */
    private function save_or_update_sync_record($member_id, $lead_id, $member)
    {
        $existing = $this->db->where('member_id', $member_id)
                             ->get(db_prefix() . 'mc_cotas_g3_sync')
                             ->row();

        $data = [
            'member_id'       => $member_id,
            'lead_id'         => $lead_id,
            'title_code'      => $member['TitleCode'] ?? null,
            'title_type_name' => $member['TitleTypeName'] ?? null,
            'member_status'   => $member['MemberStatus'] ?? null,
            'is_titular'      => ($member['Titular'] == 'Titular') ? 1 : 0,
            'last_sync_date'  => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'mc_cotas_g3_sync', $data);
        } else {
            $data['dateadded'] = date('Y-m-d H:i:s');
            $this->db->insert(db_prefix() . 'mc_cotas_g3_sync', $data);
        }
    }

    /**
     * Salvar log de sincronização
     *
     * @param array $stats
     * @return void
     */
    private function save_sync_log($stats)
    {
        $data = [
            'sync_date'      => date('Y-m-d H:i:s'),
            'total_members'  => $stats['total_members'],
            'new_leads'      => $stats['matched'] ?? 0, // Usando campo 'matched' no lugar de 'new_leads'
            'updated_leads'  => $stats['updated_leads'],
            'errors'         => $stats['errors'],
            'error_log'      => !empty($stats['error_log']) ? json_encode($stats['error_log']) : null,
            'sync_by'        => get_staff_user_id() ?: null,
            'is_cron'        => defined('CRON_RUNNING') ? 1 : 0,
            'execution_time' => $stats['execution_time'],
        ];

        $this->db->insert(db_prefix() . 'mc_cotas_g3_sync_log', $data);
    }

    /**
     * Obter histórico de sincronizações
     *
     * @param int $limit
     * @return array
     */
    public function get_sync_history($limit = 20)
    {
        return $this->db->order_by('sync_date', 'DESC')
            ->limit($limit)
            ->get(db_prefix() . 'mc_cotas_g3_sync_log')
            ->result_array();
    }

    /**
     * Obter estatísticas gerais
     *
     * @return array
     */
    public function get_stats()
    {
        $stats = [];

        // Total de leads sincronizados
        $stats['total_synced'] = $this->db->count_all_results(db_prefix() . 'mc_cotas_g3_sync');

        // Total de titulares
        $stats['total_titular'] = $this->db->where('is_titular', 1)
            ->count_all_results(db_prefix() . 'mc_cotas_g3_sync');

        // Total de dependentes
        $stats['total_dependente'] = $this->db->where('is_titular', 0)
            ->count_all_results(db_prefix() . 'mc_cotas_g3_sync');

        // Última sincronização
        $last_sync = $this->db->order_by('sync_date', 'DESC')
            ->limit(1)
            ->get(db_prefix() . 'mc_cotas_g3_sync_log')
            ->row();

        $stats['last_sync'] = $last_sync ? $last_sync->sync_date : null;

        return $stats;
    }

    /**
     * Obter estatísticas completas do dashboard
     *
     * @return array
     */
    public function get_dashboard_stats()
    {
        $stats = [];

        // Última sincronização
        $last_sync = $this->db->order_by('sync_date', 'DESC')
            ->limit(1)
            ->get(db_prefix() . 'mc_cotas_g3_sync_log')
            ->row();

        if ($last_sync) {
            $stats['last_sync'] = [
                'date' => $last_sync->sync_date,
                'total_members' => $last_sync->total_members,
                'matched' => $last_sync->new_leads, // Campo 'new_leads' guarda 'matched'
                'updated' => $last_sync->updated_leads,
                'errors' => $last_sync->errors,
                'execution_time' => $last_sync->execution_time
            ];
        } else {
            $stats['last_sync'] = null;
        }

        // Total geral de leads sincronizados
        $stats['total_synced'] = $this->db->where('mc_member_id IS NOT NULL')
            ->where('mc_member_id !=', '')
            ->where('mc_member_id !=', '0')
            ->count_all_results(db_prefix() . 'leads');

        // Ranking de consultores/vendedores (top 10)
        $stats['top_consultores'] = $this->get_top_consultores(10);

        return $stats;
    }

    /**
     * Obter ranking de consultores com mais vendas
     *
     * @param int $limit
     * @return array
     */
    public function get_top_consultores($limit = 10)
    {
        // Buscar leads agrupados por mc_vendedor
        $query = "SELECT
            l.mc_vendedor,
            COUNT(*) as total_vendas,
            s.staffid,
            s.firstname,
            s.lastname,
            s.email,
            s.phonenumber
        FROM " . db_prefix() . "leads l
        LEFT JOIN " . db_prefix() . "staff s ON (
            LOWER(CONCAT(s.firstname, ' ', s.lastname)) = LOWER(l.mc_vendedor)
            OR LOWER(CONCAT(s.lastname, ' ', s.firstname)) = LOWER(l.mc_vendedor)
            OR s.email = l.mc_vendedor
        )
        WHERE l.mc_vendedor IS NOT NULL
        AND l.mc_vendedor != ''
        GROUP BY l.mc_vendedor, s.staffid, s.firstname, s.lastname, s.email, s.phonenumber
        ORDER BY total_vendas DESC
        LIMIT " . (int)$limit;

        $result = $this->db->query($query)->result_array();

        return $result;
    }
}
