<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Instalação do módulo MC Cotas G3
 * Versão com log detalhado para debug
 */

// Função auxiliar para log
function mc_log($message) {
    $log_file = FCPATH . 'uploads/mc_cotas_g3_install.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

mc_log('===== INICIANDO INSTALAÇÃO DO MÓDULO MC COTAS G3 =====');

try {
    mc_log('Obtendo instância do CodeIgniter');
    $CI = &get_instance();
    mc_log('Instância obtida com sucesso');

    mc_log('Verificando se função db_prefix() existe');
    if (!function_exists('db_prefix')) {
        throw new Exception('Função db_prefix() não existe');
    }

    $prefix = db_prefix();
    mc_log('Prefixo do banco: ' . $prefix);

    // ====== CRIAR TABELAS ======
    mc_log('===== CRIANDO TABELAS =====');

    // Tabela mc_cotas_g3_sync
    mc_log('Verificando tabela mc_cotas_g3_sync');
    if (!$CI->db->table_exists($prefix . 'mc_cotas_g3_sync')) {
        mc_log('Tabela mc_cotas_g3_sync não existe, criando...');

        $sql = "CREATE TABLE `{$prefix}mc_cotas_g3_sync` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `member_id` INT(11) NOT NULL,
            `lead_id` INT(11) NOT NULL,
            `title_code` VARCHAR(50) NULL,
            `title_type_name` VARCHAR(191) NULL,
            `member_status` VARCHAR(50) NULL,
            `is_titular` TINYINT(1) DEFAULT 0,
            `last_sync_date` DATETIME NULL,
            `dateadded` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `member_id` (`member_id`),
            KEY `lead_id` (`lead_id`),
            KEY `title_code` (`title_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$CI->db->char_set}";

        mc_log('SQL: ' . substr($sql, 0, 100) . '...');
        $result = $CI->db->query($sql);

        if ($result) {
            mc_log('Tabela mc_cotas_g3_sync criada com sucesso');
        } else {
            throw new Exception('Erro ao criar tabela mc_cotas_g3_sync: ' . $CI->db->error()['message']);
        }
    } else {
        mc_log('Tabela mc_cotas_g3_sync já existe');
    }

    // Tabela mc_cotas_g3_sync_log
    mc_log('Verificando tabela mc_cotas_g3_sync_log');
    if (!$CI->db->table_exists($prefix . 'mc_cotas_g3_sync_log')) {
        mc_log('Tabela mc_cotas_g3_sync_log não existe, criando...');

        $sql = "CREATE TABLE `{$prefix}mc_cotas_g3_sync_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sync_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `total_members` INT(11) DEFAULT 0,
            `new_leads` INT(11) DEFAULT 0,
            `updated_leads` INT(11) DEFAULT 0,
            `errors` INT(11) DEFAULT 0,
            `error_log` TEXT NULL,
            `sync_by` INT(11) NULL,
            `is_cron` TINYINT(1) DEFAULT 0,
            `execution_time` DECIMAL(10,2) NULL,
            PRIMARY KEY (`id`),
            KEY `sync_date` (`sync_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$CI->db->char_set}";

        mc_log('SQL: ' . substr($sql, 0, 100) . '...');
        $result = $CI->db->query($sql);

        if ($result) {
            mc_log('Tabela mc_cotas_g3_sync_log criada com sucesso');
        } else {
            throw new Exception('Erro ao criar tabela mc_cotas_g3_sync_log: ' . $CI->db->error()['message']);
        }
    } else {
        mc_log('Tabela mc_cotas_g3_sync_log já existe');
    }

    // ====== ADICIONAR CAMPOS NA TABELA LEADS ======
    mc_log('===== ADICIONANDO CAMPOS NA TABELA LEADS =====');

    // Campo mc_member_id
    mc_log('Verificando campo mc_member_id');
    if (!$CI->db->field_exists('mc_member_id', $prefix . 'leads')) {
        mc_log('Campo mc_member_id não existe, adicionando...');

        $sql = "ALTER TABLE `{$prefix}leads` ADD `mc_member_id` INT(11) NULL";
        mc_log('SQL: ' . $sql);
        $result = $CI->db->query($sql);

        if ($result) {
            mc_log('Campo mc_member_id adicionado com sucesso');

            // Adicionar índice único
            mc_log('Adicionando índice único para mc_member_id');
            $index_exists = $CI->db->query("SHOW INDEX FROM `{$prefix}leads` WHERE Key_name = 'mc_member_id'")->num_rows() > 0;

            if (!$index_exists) {
                $sql = "ALTER TABLE `{$prefix}leads` ADD UNIQUE KEY `mc_member_id` (`mc_member_id`)";
                mc_log('SQL: ' . $sql);
                $result = $CI->db->query($sql);

                if ($result) {
                    mc_log('Índice único adicionado com sucesso');
                } else {
                    mc_log('AVISO: Erro ao adicionar índice único: ' . $CI->db->error()['message']);
                }
            } else {
                mc_log('Índice único já existe');
            }
        } else {
            throw new Exception('Erro ao adicionar campo mc_member_id: ' . $CI->db->error()['message']);
        }
    } else {
        mc_log('Campo mc_member_id já existe');
    }

    // Campo mc_title_code
    mc_log('Verificando campo mc_title_code');
    if (!$CI->db->field_exists('mc_title_code', $prefix . 'leads')) {
        mc_log('Campo mc_title_code não existe, adicionando...');

        $sql = "ALTER TABLE `{$prefix}leads` ADD `mc_title_code` VARCHAR(50) NULL";
        mc_log('SQL: ' . $sql);
        $result = $CI->db->query($sql);

        if ($result) {
            mc_log('Campo mc_title_code adicionado com sucesso');
        } else {
            throw new Exception('Erro ao adicionar campo mc_title_code: ' . $CI->db->error()['message']);
        }
    } else {
        mc_log('Campo mc_title_code já existe');
    }

    // Campo mc_is_titular
    mc_log('Verificando campo mc_is_titular');
    if (!$CI->db->field_exists('mc_is_titular', $prefix . 'leads')) {
        mc_log('Campo mc_is_titular não existe, adicionando...');

        $sql = "ALTER TABLE `{$prefix}leads` ADD `mc_is_titular` TINYINT(1) DEFAULT 0";
        mc_log('SQL: ' . $sql);
        $result = $CI->db->query($sql);

        if ($result) {
            mc_log('Campo mc_is_titular adicionado com sucesso');
        } else {
            throw new Exception('Erro ao adicionar campo mc_is_titular: ' . $CI->db->error()['message']);
        }
    } else {
        mc_log('Campo mc_is_titular já existe');
    }

    // ====== CRIAR OPÇÕES ======
    mc_log('===== CRIANDO OPÇÕES =====');

    $options = [
        'mc_cotas_g3_sqlserver_host' => 'aquamais.cloud.multiclubes.com.br',
        'mc_cotas_g3_sqlserver_user' => 'biaquamais',
        'mc_cotas_g3_sqlserver_password' => 'sPuu1XQhHMy@',
        'mc_cotas_g3_sqlserver_database' => 'MultiClubes',
        'mc_cotas_g3_sqlserver_port' => '1433',
        'mc_cotas_g3_auto_sync' => '0',
        'mc_cotas_g3_sync_interval' => '24',
        'mc_cotas_g3_sync_only_titular' => '1',
        'mc_cotas_g3_sync_only_active' => '1',
        'mc_cotas_g3_sync_batch_size' => '100',
        'mc_cotas_g3_default_status' => '3',
        'mc_cotas_g3_default_source' => '8',
        'mc_cotas_g3_default_assigned' => '0',
        'mc_cotas_g3_enable_detailed_log' => '1',
    ];

    foreach ($options as $name => $value) {
        mc_log("Verificando opção: $name");

        if (!function_exists('get_option')) {
            mc_log('AVISO: Função get_option() não existe, pulando criação de opções');
            break;
        }

        $existing = get_option($name);

        if (!$existing) {
            mc_log("Opção $name não existe, criando com valor: $value");

            if (function_exists('add_option')) {
                add_option($name, $value, 1);
                mc_log("Opção $name criada com sucesso");
            } else {
                mc_log('AVISO: Função add_option() não existe');
            }
        } else {
            mc_log("Opção $name já existe com valor: $existing");
        }
    }

    // ====== CRIAR PERMISSÕES ======
    mc_log('===== CRIANDO PERMISSÕES =====');

    // Verificar se a tabela de permissões existe
    mc_log('Verificando se tabela de permissões existe');
    if ($CI->db->table_exists($prefix . 'permissions')) {
        mc_log('Tabela de permissões existe');

        $role_id = 1;
        mc_log("Verificando permissões para role_id: $role_id");

        $existing_perms = $CI->db->where('feature', 'mc_cotas_g3')
                                 ->get($prefix . 'permissions')
                                 ->num_rows();

        mc_log("Permissões existentes: $existing_perms");

        if ($existing_perms == 0) {
            mc_log('Criando permissões...');

            $permissions = [
                ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'view'],
                ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'create'],
                ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'edit'],
                ['permissionid' => $role_id, 'feature' => 'mc_cotas_g3', 'capability' => 'delete'],
            ];

            foreach ($permissions as $perm) {
                mc_log("Inserindo permissão: {$perm['capability']}");
                $CI->db->insert($prefix . 'permissions', $perm);

                if ($CI->db->affected_rows() > 0) {
                    mc_log("Permissão {$perm['capability']} criada com sucesso");
                } else {
                    mc_log("AVISO: Erro ao criar permissão {$perm['capability']}");
                }
            }
        } else {
            mc_log('Permissões já existem');
        }
    } else {
        mc_log('AVISO: Tabela de permissões não existe - pulando criação de permissões');
        mc_log('NOTA: O módulo funcionará normalmente sem permissões');
    }

    // ====== LOG DE ATIVIDADE ======
    mc_log('Registrando log de atividade');

    if (function_exists('log_activity')) {
        log_activity('Módulo MC Cotas G3 instalado e ativado com sucesso');
        mc_log('Log de atividade registrado');
    } else {
        mc_log('AVISO: Função log_activity() não existe');
    }

    mc_log('===== INSTALAÇÃO CONCLUÍDA COM SUCESSO =====');

} catch (Exception $e) {
    mc_log('===== ERRO DURANTE INSTALAÇÃO =====');
    mc_log('Erro: ' . $e->getMessage());
    mc_log('Arquivo: ' . $e->getFile());
    mc_log('Linha: ' . $e->getLine());
    mc_log('Trace: ' . $e->getTraceAsString());
    mc_log('===== FIM DO LOG DE ERRO =====');

    // Tentar registrar no log de atividade
    if (function_exists('log_activity')) {
        log_activity('Erro ao instalar módulo MC Cotas G3: ' . $e->getMessage());
    }

    // Re-lançar exceção
    throw $e;
}
