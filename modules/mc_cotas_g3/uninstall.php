<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Desinstalação do módulo MC Cotas G3
 * Remove tabelas e configurações
 */

$CI = &get_instance();

/**
 * Remover tabelas criadas pelo módulo
 */
if ($CI->db->table_exists(db_prefix() . 'mc_cotas_g3_sync')) {
    $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'mc_cotas_g3_sync`');
}

if ($CI->db->table_exists(db_prefix() . 'mc_cotas_g3_sync_log')) {
    $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'mc_cotas_g3_sync_log`');
}

/**
 * Remover campos extras da tabela de leads
 * (Comentado para preservar dados, descomente se quiser remover completamente)
 */
/*
if ($CI->db->field_exists('mc_member_id', db_prefix() . 'leads')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` DROP COLUMN `mc_member_id`');
}

if ($CI->db->field_exists('mc_title_code', db_prefix() . 'leads')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` DROP COLUMN `mc_title_code`');
}

if ($CI->db->field_exists('mc_is_titular', db_prefix() . 'leads')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'leads` DROP COLUMN `mc_is_titular`');
}
*/

/**
 * Remover opções/configurações
 */
$options_to_delete = [
    'mc_cotas_g3_sqlserver_host',
    'mc_cotas_g3_sqlserver_user',
    'mc_cotas_g3_sqlserver_password',
    'mc_cotas_g3_sqlserver_database',
    'mc_cotas_g3_sqlserver_port',
    'mc_cotas_g3_auto_sync',
    'mc_cotas_g3_sync_interval',
    'mc_cotas_g3_sync_only_titular',
    'mc_cotas_g3_sync_only_active',
    'mc_cotas_g3_default_status',
    'mc_cotas_g3_default_source',
    'mc_cotas_g3_default_assigned',
    'mc_cotas_g3_enable_detailed_log',
];

foreach ($options_to_delete as $option) {
    $CI->db->where('name', $option);
    $CI->db->delete(db_prefix() . 'options');
}

/**
 * Remover permissões
 */
$CI->db->where('feature', 'mc_cotas_g3');
$CI->db->delete(db_prefix() . 'permissions');

/**
 * Log de desinstalação
 */
log_activity('Módulo MC Cotas G3 desinstalado');
