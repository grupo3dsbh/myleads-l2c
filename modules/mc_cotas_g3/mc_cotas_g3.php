<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: MC Cotas G3
Description: Integração do Multiclubes - Cotas vendidas com o MyLeads. Sincroniza membros do Multiclubes como leads no Perfex CRM.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo3 DSBH
Author URI: https://grupo3dsbh.com.br
*/

define('MC_COTAS_G3_MODULE_NAME', 'mc_cotas_g3');
define('MC_COTAS_G3_MODULE_VERSION', '1.0.0');

/**
 * Register activation hook
 */
register_activation_hook(MC_COTAS_G3_MODULE_NAME, 'mc_cotas_g3_activation_hook');

function mc_cotas_g3_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register deactivation hook
 */
register_deactivation_hook(MC_COTAS_G3_MODULE_NAME, 'mc_cotas_g3_deactivation_hook');

function mc_cotas_g3_deactivation_hook()
{
    // Código executado quando o módulo é desativado
    log_activity('Módulo MC Cotas G3 desativado');
}

/**
 * Register uninstall hook
 */
register_uninstall_hook(MC_COTAS_G3_MODULE_NAME, 'mc_cotas_g3_uninstall_hook');

function mc_cotas_g3_uninstall_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/uninstall.php');
}

/**
 * Register language files
 */
register_language_files(MC_COTAS_G3_MODULE_NAME, ['mc_cotas_g3']);

/**
 * Add menu items to admin sidebar
 */
hooks()->add_action('admin_init', 'mc_cotas_g3_init_menu_items');

function mc_cotas_g3_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('mc_cotas_g3', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('mc-cotas-g3', [
            'name'     => _l('mc_cotas_g3_menu'),
            'collapse' => true,
            'position' => 35,
            'icon'     => 'fa fa-cloud-download',
        ]);

        $CI->app_menu->add_sidebar_children_item('mc-cotas-g3', [
            'slug'     => 'mc-cotas-g3-sync',
            'name'     => _l('mc_cotas_g3_sync'),
            'href'     => admin_url('mc_cotas_g3/sync'),
            'position' => 1,
            'icon'     => 'fa fa-refresh',
        ]);

        $CI->app_menu->add_sidebar_children_item('mc-cotas-g3', [
            'slug'     => 'mc-cotas-g3-settings',
            'name'     => _l('mc_cotas_g3_settings'),
            'href'     => admin_url('mc_cotas_g3/settings'),
            'position' => 2,
            'icon'     => 'fa fa-cog',
        ]);
    }
}

/**
 * Add module permissions
 */
hooks()->add_action('admin_init', 'mc_cotas_g3_permissions');

function mc_cotas_g3_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('mc_cotas_g3', $capabilities, _l('mc_cotas_g3_module'));
}

/**
 * Register cron task for automatic sync
 */
register_cron_task('mc_cotas_g3_cron_sync');

function mc_cotas_g3_cron_sync()
{
    // Verifica se a sincronização automática está habilitada
    if (get_option('mc_cotas_g3_auto_sync') == '1') {
        $CI = &get_instance();
        $CI->load->model('mc_cotas_g3/mc_cotas_g3_model');

        try {
            $result = $CI->mc_cotas_g3_model->sync_members();
            log_activity('MC Cotas G3 - Sincronização automática executada via CRON. Resultado: ' . json_encode($result));
        } catch (Exception $e) {
            log_activity('MC Cotas G3 - Erro na sincronização automática CRON: ' . $e->getMessage());
        }
    }
}

/**
 * Inject module CSS
 */
hooks()->add_action('app_admin_head', 'mc_cotas_g3_add_head_components');

function mc_cotas_g3_add_head_components()
{
    if (strpos($_SERVER['REQUEST_URI'], 'mc_cotas_g3') !== false) {
        echo '<link href="' . module_dir_url(MC_COTAS_G3_MODULE_NAME, 'assets/css/mc_cotas_g3.css') . '?v=' . MC_COTAS_G3_MODULE_VERSION . '" rel="stylesheet" type="text/css" />';
    }
}

/**
 * Inject module JS
 */
hooks()->add_action('app_admin_footer', 'mc_cotas_g3_add_footer_components');

function mc_cotas_g3_add_footer_components()
{
    if (strpos($_SERVER['REQUEST_URI'], 'mc_cotas_g3') !== false) {
        echo '<script src="' . module_dir_url(MC_COTAS_G3_MODULE_NAME, 'assets/js/mc_cotas_g3.js') . '?v=' . MC_COTAS_G3_MODULE_VERSION . '"></script>';
    }
}
