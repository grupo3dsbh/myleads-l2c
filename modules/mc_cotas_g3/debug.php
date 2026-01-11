<?php

/**
 * Script de Debug para MC Cotas G3
 * Acesse: seu-dominio.com/modules/mc_cotas_g3/debug.php
 */

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MC Cotas G3 - Diagnóstico</h1>";
echo "<hr>";

// Verificar se está rodando no contexto do Perfex CRM
if (!defined('BASEPATH')) {
    echo "<p><strong>Carregando Perfex CRM...</strong></p>";

    // Tentar carregar o Perfex CRM
    $perfex_path = dirname(dirname(dirname(__FILE__)));
    if (file_exists($perfex_path . '/index.php')) {
        chdir($perfex_path);
        $_SERVER['REQUEST_URI'] = '/';
        require_once($perfex_path . '/index.php');
    } else {
        die("<p style='color:red'>ERRO: Não foi possível carregar o Perfex CRM. Caminho: $perfex_path</p>");
    }
}

echo "<h2>1. Extensões PHP</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Extensão</th><th>Status</th></tr>";

$extensions = ['sqlsrv', 'pdo_sqlsrv', 'pdo', 'mysqli', 'curl', 'json'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '<span style="color:green">✓ Instalada</span>' : '<span style="color:red">✗ Não instalada</span>';
    echo "<tr><td>$ext</td><td>$status</td></tr>";
}
echo "</table>";

echo "<h2>2. Configurações PHP</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Configuração</th><th>Valor</th></tr>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>max_execution_time</td><td>" . ini_get('max_execution_time') . "</td></tr>";
echo "<tr><td>memory_limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
echo "<tr><td>display_errors</td><td>" . ini_get('display_errors') . "</td></tr>";
echo "</table>";

if (defined('BASEPATH')) {
    $CI = &get_instance();

    echo "<h2>3. Banco de Dados</h2>";
    echo "<p><strong>Prefixo:</strong> " . db_prefix() . "</p>";
    echo "<p><strong>Charset:</strong> " . $CI->db->char_set . "</p>";

    echo "<h3>Tabelas do Módulo</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Tabela</th><th>Status</th></tr>";

    $tables = ['mc_cotas_g3_sync', 'mc_cotas_g3_sync_log'];
    foreach ($tables as $table) {
        $exists = $CI->db->table_exists(db_prefix() . $table);
        $status = $exists ? '<span style="color:green">✓ Existe</span>' : '<span style="color:orange">○ Não existe</span>';
        echo "<tr><td>" . db_prefix() . $table . "</td><td>$status</td></tr>";
    }
    echo "</table>";

    echo "<h3>Campos Customizados em Leads</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Status</th></tr>";

    $fields = ['mc_member_id', 'mc_title_code', 'mc_is_titular'];
    foreach ($fields as $field) {
        $exists = $CI->db->field_exists($field, db_prefix() . 'leads');
        $status = $exists ? '<span style="color:green">✓ Existe</span>' : '<span style="color:orange">○ Não existe</span>';
        echo "<tr><td>$field</td><td>$status</td></tr>";
    }
    echo "</table>";

    echo "<h2>4. Opções do Módulo</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Opção</th><th>Valor</th></tr>";

    $options = [
        'mc_cotas_g3_sqlserver_host',
        'mc_cotas_g3_sqlserver_user',
        'mc_cotas_g3_sqlserver_database',
        'mc_cotas_g3_sqlserver_port',
        'mc_cotas_g3_auto_sync',
        'mc_cotas_g3_default_status',
        'mc_cotas_g3_default_source'
    ];

    foreach ($options as $option) {
        $value = get_option($option);
        $display_value = ($option == 'mc_cotas_g3_sqlserver_password') ? '********' : ($value ?: '<em>não definido</em>');
        echo "<tr><td>$option</td><td>$display_value</td></tr>";
    }
    echo "</table>";

    echo "<h2>5. Permissões</h2>";
    $perms = $CI->db->where('feature', 'mc_cotas_g3')->get(db_prefix() . 'permissions')->result_array();
    if (!empty($perms)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Role ID</th><th>Capability</th></tr>";
        foreach ($perms as $perm) {
            echo "<tr><td>" . $perm['permissionid'] . "</td><td>" . $perm['capability'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>Nenhuma permissão encontrada</p>";
    }

    echo "<h2>6. Verificar Estrutura da Tabela Leads</h2>";
    $query = $CI->db->query("DESCRIBE " . db_prefix() . "leads");
    $fields_info = $query->result_array();

    echo "<p><strong>Total de campos:</strong> " . count($fields_info) . "</p>";
    echo "<details>";
    echo "<summary>Ver todos os campos (clique para expandir)</summary>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    foreach ($fields_info as $field) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . ($field['Key'] ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</details>";

    echo "<h2>7. Teste de Instalação</h2>";
    echo "<p>Tentando executar o script de instalação...</p>";

    try {
        ob_start();
        require_once(__DIR__ . '/install.php');
        $output = ob_get_clean();
        echo "<p style='color:green'><strong>✓ Script de instalação executado com sucesso!</strong></p>";
        if (!empty($output)) {
            echo "<pre>$output</pre>";
        }
    } catch (Exception $e) {
        $output = ob_get_clean();
        echo "<p style='color:red'><strong>✗ Erro ao executar script de instalação:</strong></p>";
        echo "<pre style='background:#ffeeee; padding:10px; border:1px solid #ff0000;'>";
        echo "Mensagem: " . $e->getMessage() . "\n\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n\n";
        echo "Trace:\n" . $e->getTraceAsString();
        echo "</pre>";
        if (!empty($output)) {
            echo "<p><strong>Output capturado:</strong></p>";
            echo "<pre>$output</pre>";
        }
    }

    echo "<h2>8. Logs de Atividade</h2>";
    $logs = $CI->db->where('description LIKE', '%MC Cotas G3%')
                   ->order_by('date', 'DESC')
                   ->limit(10)
                   ->get(db_prefix() . 'activity_log')
                   ->result_array();

    if (!empty($logs)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Data</th><th>Descrição</th></tr>";
        foreach ($logs as $log) {
            echo "<tr><td>" . $log['date'] . "</td><td>" . $log['description'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum log encontrado</p>";
    }
}

echo "<hr>";
echo "<p><em>Debug gerado em: " . date('Y-m-d H:i:s') . "</em></p>";
