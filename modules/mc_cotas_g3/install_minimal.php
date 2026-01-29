<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * INSTALAÇÃO MINIMALISTA - Apenas o essencial
 * Use este arquivo se install.php estiver dando erro
 *
 * Para usar:
 * 1. Renomeie install.php para install_backup.php
 * 2. Renomeie install_minimal.php para install.php
 * 3. Tente ativar novamente
 */

$CI = &get_instance();

// Log em arquivo
$log = function($msg) {
    @file_put_contents(FCPATH . 'uploads/mc_minimal.log', date('H:i:s') . " - $msg\n", FILE_APPEND);
};

$log('INÍCIO');

// Apenas criar opções básicas
$log('Criando opções');
if (function_exists('add_option')) {
    add_option('mc_cotas_g3_sqlserver_host', 'aquamais.cloud.multiclubes.com.br');
    add_option('mc_cotas_g3_sqlserver_user', 'biaquamais');
    add_option('mc_cotas_g3_sqlserver_password', 'sPuu1XQhHMy@');
    add_option('mc_cotas_g3_sqlserver_database', 'MultiClubes');
    add_option('mc_cotas_g3_sqlserver_port', '1433');
}

$log('FIM');
