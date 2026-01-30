<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Script de Migração - MC Cotas G3
 * Adiciona campos e tabelas que não existem
 */

$CI = &get_instance();

echo "<h1>MC Cotas G3 - Migração de Banco de Dados</h1>";
echo "<hr>";

$prefix = db_prefix();
$changes = [];

// ====== CRIAR TABELA DE PROMOTORES/CONSULTORES ======
echo "<h2>1. Verificando tabela de promotores...</h2>";

if (!$CI->db->table_exists($prefix . 'mc_cotas_g3_promotores')) {
    echo "<p>Criando tabela mc_cotas_g3_promotores...</p>";

    $sql = "CREATE TABLE `{$prefix}mc_cotas_g3_promotores` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `codigo` VARCHAR(50) NULL COMMENT 'Código do promotor no Multiclubes',
        `nome` VARCHAR(191) NOT NULL COMMENT 'Nome do promotor/consultor',
        `email` VARCHAR(100) NULL,
        `telefone` VARCHAR(50) NULL,
        `ativo` TINYINT(1) DEFAULT 1,
        `total_vendas` INT(11) DEFAULT 0,
        `dateadded` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `codigo` (`codigo`),
        KEY `nome` (`nome`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$CI->db->char_set}";

    $CI->db->query($sql);
    echo "<p style='color:green'>✓ Tabela criada com sucesso!</p>";
    $changes[] = "Tabela mc_cotas_g3_promotores criada";
} else {
    echo "<p style='color:blue'>✓ Tabela mc_cotas_g3_promotores já existe</p>";
}

// ====== ADICIONAR CAMPO PROMOTOR NA TABELA SYNC ======
echo "<h2>2. Verificando campo promotor_id na sync...</h2>";

if ($CI->db->table_exists($prefix . 'mc_cotas_g3_sync')) {
    if (!$CI->db->field_exists('promotor_id', $prefix . 'mc_cotas_g3_sync')) {
        echo "<p>Adicionando campo promotor_id...</p>";
        $CI->db->query("ALTER TABLE `{$prefix}mc_cotas_g3_sync` ADD `promotor_id` INT(11) NULL COMMENT 'ID do promotor' AFTER `is_titular`");
        echo "<p style='color:green'>✓ Campo promotor_id adicionado!</p>";
        $changes[] = "Campo promotor_id adicionado em mc_cotas_g3_sync";
    } else {
        echo "<p style='color:blue'>✓ Campo promotor_id já existe</p>";
    }
} else {
    echo "<p style='color:red'>✗ Tabela mc_cotas_g3_sync não existe!</p>";
}

// ====== ADICIONAR CAMPO VENDEDOR NA TABELA LEADS ======
echo "<h2>3. Verificando campo mc_vendedor em leads...</h2>";

if (!$CI->db->field_exists('mc_vendedor', $prefix . 'leads')) {
    echo "<p>Adicionando campo mc_vendedor...</p>";
    $CI->db->query("ALTER TABLE `{$prefix}leads` ADD `mc_vendedor` VARCHAR(191) NULL COMMENT 'Nome do vendedor/consultor Multiclubes'");
    echo "<p style='color:green'>✓ Campo mc_vendedor adicionado!</p>";
    $changes[] = "Campo mc_vendedor adicionado em leads";
} else {
    echo "<p style='color:blue'>✓ Campo mc_vendedor já existe</p>";
}

// ====== ADICIONAR CAMPO DATA DA VENDA ======
echo "<h2>4. Verificando campo mc_data_venda em leads...</h2>";

if (!$CI->db->field_exists('mc_data_venda', $prefix . 'leads')) {
    echo "<p>Adicionando campo mc_data_venda...</p>";
    $CI->db->query("ALTER TABLE `{$prefix}leads` ADD `mc_data_venda` DATETIME NULL COMMENT 'Data da venda no Multiclubes'");
    echo "<p style='color:green'>✓ Campo mc_data_venda adicionado!</p>";
    $changes[] = "Campo mc_data_venda adicionado em leads";
} else {
    echo "<p style='color:blue'>✓ Campo mc_data_venda já existe</p>";
}

// ====== ADICIONAR OPÇÕES NOVAS ======
echo "<h2>5. Verificando novas opções...</h2>";

$new_options = [
    'mc_cotas_g3_match_phone_digits' => '8',
    'mc_cotas_g3_update_status_on_match' => '1',
    'mc_cotas_g3_closed_status_name' => 'Customer',
];

foreach ($new_options as $name => $value) {
    if (!get_option($name)) {
        add_option($name, $value, 1);
        echo "<p style='color:green'>✓ Opção $name criada</p>";
        $changes[] = "Opção $name criada";
    } else {
        echo "<p style='color:blue'>✓ Opção $name já existe</p>";
    }
}

// ====== RESUMO ======
echo "<hr>";
echo "<h2>Resumo</h2>";

if (empty($changes)) {
    echo "<p style='color:blue'><strong>Nenhuma alteração necessária. Banco de dados já está atualizado!</strong></p>";
} else {
    echo "<p style='color:green'><strong>Migração concluída com sucesso!</strong></p>";
    echo "<ul>";
    foreach ($changes as $change) {
        echo "<li>$change</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='" . admin_url('mc_cotas_g3/sync') . "' class='btn btn-primary'>Ir para Sincronização</a></p>";
