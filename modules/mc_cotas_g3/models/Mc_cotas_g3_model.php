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
     * Sincronizar membros do Multiclubes com leads do MyLeads CRM
     *
     * @param array $options
     * @return array
     */
    public function sync_members($options = [])
    {
        $start_time = microtime(true);

        $stats = [
            'success'        => false,
            'total_members'  => 0,
            'new_leads'      => 0,
            'updated_leads'  => 0,
            'skipped'        => 0,
            'errors'         => 0,
            'error_log'      => [],
            'execution_time' => 0,
            'batches'        => 0,
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

            // Tamanho do lote (batch)
            $batch_size = (int)get_option('mc_cotas_g3_sync_batch_size') ?: 100;

            // Calcular número de lotes
            $total_batches = ceil($total_members / $batch_size);

            // Processar em lotes
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
                        $result = $this->process_member($member);

                        if ($result['action'] == 'created') {
                            $stats['new_leads']++;
                        } elseif ($result['action'] == 'updated') {
                            $stats['updated_leads']++;
                        } elseif ($result['action'] == 'skipped') {
                            $stats['skipped']++;
                        }
                    } catch (Exception $e) {
                        $stats['errors']++;
                        $stats['error_log'][] = [
                            'member_id'   => $member['MemberId'] ?? 'N/A',
                            'member_name' => $member['MemberName'] ?? 'N/A',
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
     * Processar um membro individual
     *
     * @param array $member
     * @return array
     */
    private function process_member($member)
    {
        $member_id = $member['MemberId'];

        // Verificar se o membro já existe como lead
        $existing_lead = $this->db->where('mc_member_id', $member_id)
            ->get(db_prefix() . 'leads')
            ->row();

        // Preparar dados do lead
        $lead_data = $this->prepare_lead_data($member);

        if ($existing_lead) {
            // Atualizar lead existente
            $this->db->where('id', $existing_lead->id);
            $this->db->update(db_prefix() . 'leads', $lead_data);

            // Atualizar registro de sincronização
            $this->update_sync_record($member_id, $existing_lead->id, $member);

            return ['action' => 'updated', 'lead_id' => $existing_lead->id];
        } else {
            // Criar novo lead
            $lead_data['dateadded'] = date('Y-m-d H:i:s');
            $lead_data['addedfrom'] = get_staff_user_id() ?: 0;
            $lead_data['hash'] = app_generate_hash();
            $lead_data['lastcontact'] = null;
            $lead_data['status'] = get_option('mc_cotas_g3_default_status');
            $lead_data['source'] = get_option('mc_cotas_g3_default_source');

            // Atribuir a um staff se configurado
            $assigned = get_option('mc_cotas_g3_default_assigned');
            if ($assigned && $assigned > 0) {
                $lead_data['assigned'] = $assigned;
                $lead_data['dateassigned'] = date('Y-m-d');
            }

            $this->db->insert(db_prefix() . 'leads', $lead_data);
            $lead_id = $this->db->insert_id();

            // Criar registro de sincronização
            $this->create_sync_record($member_id, $lead_id, $member);

            return ['action' => 'created', 'lead_id' => $lead_id];
        }
    }

    /**
     * Preparar dados do lead a partir do membro
     *
     * @param array $member
     * @return array
     */
    private function prepare_lead_data($member)
    {
        // Formatar telefone
        $phone = $this->format_phone($member['MemberMobilePhone'] ?? '');

        // Preparar endereço
        $address = $this->format_address($member);

        // Preparar descrição
        $description = $this->prepare_description($member);

        return [
            'name'           => trim($member['MemberName'] ?? ''),
            'email'          => trim($member['MemberEmail'] ?? ''),
            'phonenumber'    => $phone,
            'address'        => $address['street'],
            'city'           => $member['AdressCity'] ?? '',
            'state'          => $member['AdressState'] ?? '',
            'zip'            => '',
            'country'        => 0, // Brasil
            'description'    => $description,
            'mc_member_id'   => $member['MemberId'],
            'mc_title_code'  => $member['TitleCode'] ?? '',
            'mc_is_titular'  => ($member['Titular'] == 'Titular') ? 1 : 0,
        ];
    }

    /**
     * Formatar telefone
     *
     * @param string $phone
     * @return string
     */
    private function format_phone($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Remover caracteres especiais
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Adicionar código do país se não tiver
        if (strlen($phone) == 11 && substr($phone, 0, 2) != '55') {
            $phone = '55' . $phone;
        }

        return $phone;
    }

    /**
     * Formatar endereço
     *
     * @param array $member
     * @return array
     */
    private function format_address($member)
    {
        $parts = [];

        if (!empty($member['AdressStreet'])) {
            $parts[] = $member['AdressStreet'];
        }

        if (!empty($member['AdressNumber'])) {
            $parts[] = 'n° ' . $member['AdressNumber'];
        }

        if (!empty($member['AdressComplement'])) {
            $parts[] = $member['AdressComplement'];
        }

        if (!empty($member['AdressBurgh'])) {
            $parts[] = $member['AdressBurgh'];
        }

        $street = implode(', ', $parts);

        return [
            'street' => $street,
            'city'   => $member['AdressCity'] ?? '',
            'state'  => $member['AdressState'] ?? '',
        ];
    }

    /**
     * Preparar descrição do lead
     *
     * @param array $member
     * @return string
     */
    private function prepare_description($member)
    {
        $description = "**IMPORTADO DO MULTICLUBES**\n\n";

        if (!empty($member['TitleTypeName'])) {
            $description .= "**Título:** " . $member['TitleTypeName'] . " (" . ($member['TitleCode'] ?? '') . ")\n";
        }

        if (!empty($member['Titular'])) {
            $description .= "**Tipo:** " . $member['Titular'] . "\n";
        }

        if (!empty($member['MemberStatus'])) {
            $description .= "**Status:** " . $member['MemberStatus'] . "\n";
        }

        if (!empty($member['MemberDocumentNumber'])) {
            $description .= "**CPF:** " . $member['MemberDocumentNumber'] . "\n";
        }

        if (!empty($member['MemberBirthDate'])) {
            $birthDate = date('d/m/Y', strtotime($member['MemberBirthDate']));
            $description .= "**Data de Nascimento:** " . $birthDate;
            if (!empty($member['MemberAge'])) {
                $description .= " (" . $member['MemberAge'] . " anos)";
            }
            $description .= "\n";
        }

        if (!empty($member['MemberSex'])) {
            $description .= "**Sexo:** " . $member['MemberSex'] . "\n";
        }

        if (!empty($member['ParentageName'])) {
            $description .= "**Parentesco:** " . $member['ParentageName'] . "\n";
        }

        if (!empty($member['LastUpdateDate'])) {
            $updateDate = date('d/m/Y H:i', strtotime($member['LastUpdateDate']));
            $description .= "\n**Última atualização no Multiclubes:** " . $updateDate;
        }

        return $description;
    }

    /**
     * Criar registro de sincronização
     *
     * @param int $member_id
     * @param int $lead_id
     * @param array $member
     * @return void
     */
    private function create_sync_record($member_id, $lead_id, $member)
    {
        $data = [
            'member_id'       => $member_id,
            'lead_id'         => $lead_id,
            'title_code'      => $member['TitleCode'] ?? null,
            'title_type_name' => $member['TitleTypeName'] ?? null,
            'member_status'   => $member['MemberStatus'] ?? null,
            'is_titular'      => ($member['Titular'] == 'Titular') ? 1 : 0,
            'last_sync_date'  => date('Y-m-d H:i:s'),
            'dateadded'       => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'mc_cotas_g3_sync', $data);
    }

    /**
     * Atualizar registro de sincronização
     *
     * @param int $member_id
     * @param int $lead_id
     * @param array $member
     * @return void
     */
    private function update_sync_record($member_id, $lead_id, $member)
    {
        $data = [
            'title_code'      => $member['TitleCode'] ?? null,
            'title_type_name' => $member['TitleTypeName'] ?? null,
            'member_status'   => $member['MemberStatus'] ?? null,
            'is_titular'      => ($member['Titular'] == 'Titular') ? 1 : 0,
            'last_sync_date'  => date('Y-m-d H:i:s'),
        ];

        $this->db->where('member_id', $member_id);
        $this->db->update(db_prefix() . 'mc_cotas_g3_sync', $data);
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
            'new_leads'      => $stats['new_leads'],
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
}
