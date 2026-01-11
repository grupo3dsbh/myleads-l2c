<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Instalação do módulo MC Cotas G3
 * Cria tabelas e configurações iniciais
 */

$CI = &get_instance();

/**
 * Criar tabela de sincronização de membros
 * Armazena o relacionamento entre membros do Multiclubes e leads do Perfex CRM
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
 * Armazena histórico de todas as sincronizações
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
 * Adicionar campos extras na tabela de leads (se necessário)
 * Campo para armazenar o MemberId do Multiclubes
 */
if (!$CI->db->field_exists('mc_member_id', db_prefix() . 'leads')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `mc_member_id` INT(11) NULL COMMENT "ID do membro no Multiclubes" AFTER `lead_value`');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD UNIQUE KEY `mc_member_id` (`mc_member_id`)');
}

if (!$CI->db->field_exists('mc_title_code', db_prefix() . 'leads')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `mc_title_code` VARCHAR(50) NULL COMMENT "Código do título Multiclubes" AFTER `mc_member_id`');
}

if (!$CI->db->field_exists('mc_is_titular', db_prefix() . 'leads')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `mc_is_titular` TINYINT(1) DEFAULT 0 COMMENT "Se é titular ou dependente" AFTER `mc_title_code`');
}

/**
 * Criar configurações padrão
 */

// Configurações de conexão SQL Server
add_option('mc_cotas_g3_sqlserver_host', 'aquamais.cloud.multiclubes.com.br', 1);
add_option('mc_cotas_g3_sqlserver_user', 'biaquamais', 1);
add_option('mc_cotas_g3_sqlserver_password', 'sPuu1XQhHMy@', 1); // Será criptografado
add_option('mc_cotas_g3_sqlserver_database', 'MultiClubes', 1);
add_option('mc_cotas_g3_sqlserver_port', '1433', 1);

// Configurações de sincronização
add_option('mc_cotas_g3_auto_sync', '0', 1); // Sincronização automática desabilitada por padrão
add_option('mc_cotas_g3_sync_interval', '24', 1); // Intervalo em horas
add_option('mc_cotas_g3_sync_only_titular', '1', 1); // Sincronizar apenas titulares por padrão
add_option('mc_cotas_g3_sync_only_active', '1', 1); // Sincronizar apenas membros ativos

// Configurações de mapeamento
add_option('mc_cotas_g3_default_status', '3', 1); // ID do status padrão (SEM ATENDIMENTO)
add_option('mc_cotas_g3_default_source', '8', 1); // ID da fonte padrão (CRM)
add_option('mc_cotas_g3_default_assigned', '0', 1); // Não atribuir por padrão

// Configuração de log
add_option('mc_cotas_g3_enable_detailed_log', '1', 1);

/**
 * Log de ativação
 */
log_activity('Módulo MC Cotas G3 instalado e ativado com sucesso');

/**
 * Criar permissões padrão para administradores
 */
$role_id = 1; // ID do papel de administrador
if (!$CI->db->get_where(db_prefix() . 'permissions', ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3'])->row()) {
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
