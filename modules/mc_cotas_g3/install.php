<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Instalação do módulo MC Cotas G3
 * Cria tabelas e configurações iniciais
 */

$CI = &get_instance();

try {
    /**
     * Criar tabela de sincronização de membros
     */
    if (!$CI->db->table_exists(db_prefix() . 'mc_cotas_g3_sync')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . "mc_cotas_g3_sync` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `member_id` INT(11) NOT NULL COMMENT 'ID do membro no Multiclubes',
            `lead_id` INT(11) NOT NULL COMMENT 'ID do lead no Perfex CRM',
            `title_code` VARCHAR(50) NULL COMMENT 'Código do título',
            `title_type_name` VARCHAR(191) NULL COMMENT 'Nome do tipo de título',
            `member_status` VARCHAR(50) NULL COMMENT 'Status do membro no Multiclubes',
            `is_titular` TINYINT(1) DEFAULT 0 COMMENT 'Se é titular ou dependente',
            `last_sync_date` DATETIME NULL COMMENT 'Data da última sincronização',
            `dateadded` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `member_id` (`member_id`),
            KEY `lead_id` (`lead_id`),
            KEY `title_code` (`title_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    }

    /**
     * Criar tabela de log de sincronização
     */
    if (!$CI->db->table_exists(db_prefix() . 'mc_cotas_g3_sync_log')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . "mc_cotas_g3_sync_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sync_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `total_members` INT(11) DEFAULT 0 COMMENT 'Total de membros encontrados',
            `new_leads` INT(11) DEFAULT 0 COMMENT 'Novos leads criados',
            `updated_leads` INT(11) DEFAULT 0 COMMENT 'Leads atualizados',
            `errors` INT(11) DEFAULT 0 COMMENT 'Erros durante sincronização',
            `error_log` TEXT NULL COMMENT 'Log de erros em JSON',
            `sync_by` INT(11) NULL COMMENT 'ID do staff que iniciou a sincronização',
            `is_cron` TINYINT(1) DEFAULT 0 COMMENT 'Se foi executado via CRON',
            `execution_time` DECIMAL(10,2) NULL COMMENT 'Tempo de execução em segundos',
            PRIMARY KEY (`id`),
            KEY `sync_date` (`sync_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    }

    /**
     * Adicionar campos extras na tabela de leads
     */
    if (!$CI->db->field_exists('mc_member_id', db_prefix() . 'leads')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `mc_member_id` INT(11) NULL COMMENT "ID do membro no Multiclubes"');

        // Adicionar índice único apenas se não existir
        $index_exists = $CI->db->query("SHOW INDEX FROM `" . db_prefix() . "leads` WHERE Key_name = 'mc_member_id'")->num_rows() > 0;
        if (!$index_exists) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD UNIQUE KEY `mc_member_id` (`mc_member_id`)');
        }
    }

    if (!$CI->db->field_exists('mc_title_code', db_prefix() . 'leads')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `mc_title_code` VARCHAR(50) NULL COMMENT "Código do título Multiclubes"');
    }

    if (!$CI->db->field_exists('mc_is_titular', db_prefix() . 'leads')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `mc_is_titular` TINYINT(1) DEFAULT 0 COMMENT "Se é titular ou dependente"');
    }

    /**
     * Criar configurações padrão
     */
    // Configurações de conexão SQL Server
    if (!get_option('mc_cotas_g3_sqlserver_host')) {
        add_option('mc_cotas_g3_sqlserver_host', 'aquamais.cloud.multiclubes.com.br', 1);
    }
    if (!get_option('mc_cotas_g3_sqlserver_user')) {
        add_option('mc_cotas_g3_sqlserver_user', 'biaquamais', 1);
    }
    if (!get_option('mc_cotas_g3_sqlserver_password')) {
        add_option('mc_cotas_g3_sqlserver_password', 'sPuu1XQhHMy@', 1);
    }
    if (!get_option('mc_cotas_g3_sqlserver_database')) {
        add_option('mc_cotas_g3_sqlserver_database', 'MultiClubes', 1);
    }
    if (!get_option('mc_cotas_g3_sqlserver_port')) {
        add_option('mc_cotas_g3_sqlserver_port', '1433', 1);
    }

    // Configurações de sincronização
    if (!get_option('mc_cotas_g3_auto_sync')) {
        add_option('mc_cotas_g3_auto_sync', '0', 1);
    }
    if (!get_option('mc_cotas_g3_sync_interval')) {
        add_option('mc_cotas_g3_sync_interval', '24', 1);
    }
    if (!get_option('mc_cotas_g3_sync_only_titular')) {
        add_option('mc_cotas_g3_sync_only_titular', '1', 1);
    }
    if (!get_option('mc_cotas_g3_sync_only_active')) {
        add_option('mc_cotas_g3_sync_only_active', '1', 1);
    }

    // Configurações de mapeamento
    if (!get_option('mc_cotas_g3_default_status')) {
        add_option('mc_cotas_g3_default_status', '3', 1);
    }
    if (!get_option('mc_cotas_g3_default_source')) {
        add_option('mc_cotas_g3_default_source', '8', 1);
    }
    if (!get_option('mc_cotas_g3_default_assigned')) {
        add_option('mc_cotas_g3_default_assigned', '0', 1);
    }

    // Configuração de log
    if (!get_option('mc_cotas_g3_enable_detailed_log')) {
        add_option('mc_cotas_g3_enable_detailed_log', '1', 1);
    }

    /**
     * Criar permissões padrão para administradores
     */
    $role_id = 1; // ID do papel de administrador

    // Verificar se já existe alguma permissão antes de inserir
    $existing_perms = $CI->db->where('feature', 'mc_cotas_g3')->get(db_prefix() . 'permissions')->num_rows();

    if ($existing_perms == 0) {
        $permissions = [
            ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'view'],
            ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'create'],
            ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'edit'],
            ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'delete'],
        ];

        foreach ($permissions as $permission) {
            $CI->db->insert(db_prefix() . 'permissions', $permission);
        }
    }

    /**
     * Log de ativação
     */
    log_activity('Módulo MC Cotas G3 instalado e ativado com sucesso');

} catch (Exception $e) {
    // Log do erro
    log_activity('Erro ao instalar módulo MC Cotas G3: ' . $e->getMessage());

    // Tentar escrever em arquivo de log
    $log_file = FCPATH . 'uploads/mc_cotas_g3_install_error.log';
    $error_message = date('Y-m-d H:i:s') . ' - Erro: ' . $e->getMessage() . "\n";
    $error_message .= 'Trace: ' . $e->getTraceAsString() . "\n\n";
    @file_put_contents($log_file, $error_message, FILE_APPEND);

    // Re-lançar exceção para mostrar erro
    throw $e;
}
