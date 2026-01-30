<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mc_cotas_g3 extends AdminController
{
    private $has_permissions_system = false;

    public function __construct()
    {
        parent::__construct();

        // Verificar se o sistema de permissões existe
        $this->has_permissions_system = $this->db->table_exists(db_prefix() . 'permissions');

        // Verificar permissão (se o sistema de permissões existir)
        if ($this->has_permissions_system && function_exists('has_permission')) {
            if (!$this->check_permission('view')) {
                access_denied('mc_cotas_g3');
            }
        }

        // Carregar model
        $this->load->model('mc_cotas_g3/mc_cotas_g3_model');
    }

    /**
     * Verificar permissão (compatível com sistemas sem tabela de permissões)
     */
    private function check_permission($capability = 'view')
    {
        if (!$this->has_permissions_system) {
            // Se não há sistema de permissões, permitir acesso (apenas para admins)
            return is_admin();
        }

        if (function_exists('has_permission')) {
            return has_permission('mc_cotas_g3', '', $capability);
        }

        return is_admin();
    }

    /**
     * Página de sincronização
     */
    public function index()
    {
        redirect(admin_url('mc_cotas_g3/sync'));
    }

    /**
     * Página de sincronização
     */
    public function sync()
    {
        if ($this->input->post()) {
            if (!$this->check_permission('create')) {
                access_denied('mc_cotas_g3');
            }

            $this->sync_now();
            return;
        }

        $data['title'] = _l('mc_cotas_g3_sync');
        $data['stats'] = $this->mc_cotas_g3_model->get_stats();
        $data['dashboard_stats'] = $this->mc_cotas_g3_model->get_dashboard_stats();
        $data['history'] = $this->mc_cotas_g3_model->get_sync_history(10);

        // Verificar se pode sincronizar (intervalo de 6h)
        $last_sync_time = get_option('mc_cotas_g3_last_sync_timestamp');
        $can_sync = true;
        $time_remaining = 0;

        if ($last_sync_time) {
            $interval_seconds = 6 * 3600; // 6 horas
            $time_remaining = $interval_seconds - (time() - $last_sync_time);
            $can_sync = $time_remaining <= 0;
        }

        $data['can_sync'] = $can_sync;
        $data['time_remaining'] = $time_remaining;

        $this->load->view('mc_cotas_g3/sync', $data);
    }

    /**
     * Executar sincronização agora
     */
    public function sync_now()
    {
        if (!$this->check_permission('create')) {
            ajax_access_denied();
        }

        // Verificar intervalo mínimo de 6h entre sincronizações
        $last_sync_time = get_option('mc_cotas_g3_last_sync_timestamp');
        $interval_hours = 6;
        $interval_seconds = $interval_hours * 3600;

        if ($last_sync_time && (time() - $last_sync_time) < $interval_seconds) {
            $time_remaining = $interval_seconds - (time() - $last_sync_time);
            $hours_remaining = floor($time_remaining / 3600);
            $minutes_remaining = floor(($time_remaining % 3600) / 60);

            $message = sprintf(
                _l('mc_cotas_g3_sync_interval_error'),
                $hours_remaining,
                $minutes_remaining
            );

            set_alert('warning', $message);
            redirect(admin_url('mc_cotas_g3/sync'));
            return;
        }

        set_time_limit(300); // 5 minutos

        try {
            $result = $this->mc_cotas_g3_model->sync_members();

            if ($result['success']) {
                // Atualizar timestamp da última sincronização
                update_option('mc_cotas_g3_last_sync_timestamp', time());

                $message = sprintf(
                    _l('mc_cotas_g3_sync_success'),
                    $result['total_members'],
                    $result['matched'],
                    $result['updated_leads'],
                    $result['not_matched'],
                    $result['errors']
                );

                set_alert('success', $message);

                log_activity('MC Cotas G3 - Sincronização manual executada com sucesso');
            } else {
                $error_msg = !empty($result['error_log']) ? json_encode($result['error_log']) : 'Erro desconhecido';
                set_alert('danger', _l('mc_cotas_g3_sync_error') . ': ' . $error_msg);
            }
        } catch (Exception $e) {
            set_alert('danger', _l('mc_cotas_g3_sync_error') . ': ' . $e->getMessage());
            log_activity('MC Cotas G3 - Erro na sincronização manual: ' . $e->getMessage());
        }

        redirect(admin_url('mc_cotas_g3/sync'));
    }

    /**
     * Testar conexão
     */
    public function test_connection()
    {
        if (!$this->check_permission('view')) {
            ajax_access_denied();
        }

        $result = $this->mc_cotas_g3_model->test_connection();

        echo json_encode($result);
    }

    /**
     * Página de configurações
     */
    public function settings()
    {
        if (!$this->check_permission('edit')) {
            access_denied('mc_cotas_g3');
        }

        if ($this->input->post()) {
            $this->save_settings();
            return;
        }

        $data['title'] = _l('mc_cotas_g3_settings');

        // Buscar todas as opções de status de leads
        $data['lead_statuses'] = $this->db->order_by('statusorder', 'ASC')
            ->get(db_prefix() . 'leads_status')
            ->result_array();

        // Buscar todas as fontes de leads
        $data['lead_sources'] = $this->db->order_by('name', 'ASC')
            ->get(db_prefix() . 'leads_sources')
            ->result_array();

        // Buscar todos os membros da equipe
        $data['staff'] = $this->db->where('active', 1)
            ->order_by('firstname', 'ASC')
            ->get(db_prefix() . 'staff')
            ->result_array();

        $this->load->view('mc_cotas_g3/settings', $data);
    }

    /**
     * Salvar configurações
     */
    private function save_settings()
    {
        if (!$this->check_permission('edit')) {
            ajax_access_denied();
        }

        $post_data = $this->input->post();

        // Configurações de conexão SQL Server
        $options = [
            'mc_cotas_g3_sqlserver_host',
            'mc_cotas_g3_sqlserver_user',
            'mc_cotas_g3_sqlserver_password',
            'mc_cotas_g3_sqlserver_database',
            'mc_cotas_g3_sqlserver_port',
            'mc_cotas_g3_auto_sync',
            'mc_cotas_g3_sync_interval',
            'mc_cotas_g3_sync_only_titular',
            'mc_cotas_g3_sync_only_active',
            'mc_cotas_g3_sync_batch_size',
            'mc_cotas_g3_default_status',
            'mc_cotas_g3_default_source',
            'mc_cotas_g3_default_assigned',
            'mc_cotas_g3_enable_detailed_log',
        ];

        foreach ($options as $option) {
            $value = isset($post_data[$option]) ? $post_data[$option] : '';

            // Tratar checkboxes
            if (in_array($option, [
                'mc_cotas_g3_auto_sync',
                'mc_cotas_g3_sync_only_titular',
                'mc_cotas_g3_sync_only_active',
                'mc_cotas_g3_enable_detailed_log'
            ])) {
                $value = isset($post_data[$option]) && $post_data[$option] == 'on' ? '1' : '0';
            }

            update_option($option, $value);
        }

        set_alert('success', _l('settings_updated'));
        log_activity('MC Cotas G3 - Configurações atualizadas');

        redirect(admin_url('mc_cotas_g3/settings'));
    }

    /**
     * Obter histórico de sincronização via AJAX
     */
    public function get_sync_history()
    {
        if (!$this->check_permission('view')) {
            ajax_access_denied();
        }

        $limit = $this->input->get('limit') ?: 20;
        $history = $this->mc_cotas_g3_model->get_sync_history($limit);

        echo json_encode($history);
    }

    /**
     * Visualizar log detalhado de uma sincronização
     */
    public function view_sync_log($sync_id)
    {
        if (!$this->check_permission('view')) {
            access_denied('mc_cotas_g3');
        }

        $log = $this->db->where('id', $sync_id)
            ->get(db_prefix() . 'mc_cotas_g3_sync_log')
            ->row();

        if (!$log) {
            show_404();
        }

        $data['title'] = _l('mc_cotas_g3_sync_log_details');
        $data['log'] = $log;

        // Decodificar error_log se existir
        if (!empty($log->error_log)) {
            $data['errors'] = json_decode($log->error_log, true);
        } else {
            $data['errors'] = [];
        }

        $this->load->view('mc_cotas_g3/sync_log_details', $data);
    }

    /**
     * Limpar histórico de sincronização
     */
    public function clear_history()
    {
        if (!$this->check_permission('delete')) {
            ajax_access_denied();
        }

        $days = $this->input->post('days') ?: 30;

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $this->db->where('sync_date <', $date);
        $deleted = $this->db->delete(db_prefix() . 'mc_cotas_g3_sync_log');

        if ($deleted) {
            set_alert('success', _l('mc_cotas_g3_history_cleared'));
            log_activity('MC Cotas G3 - Histórico de sincronização limpo (mais de ' . $days . ' dias)');
        } else {
            set_alert('warning', _l('mc_cotas_g3_no_history_to_clear'));
        }

        redirect(admin_url('mc_cotas_g3/sync'));
    }
}
