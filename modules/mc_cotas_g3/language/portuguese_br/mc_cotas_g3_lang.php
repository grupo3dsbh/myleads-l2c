<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Arquivo de idioma - Português (Brasil)
 * Módulo MC Cotas G3
 */

// Menu
$lang['mc_cotas_g3_menu'] = 'MC Cotas G3';
$lang['mc_cotas_g3_module'] = 'MC Cotas G3';
$lang['mc_cotas_g3_sync'] = 'Sincronização';
$lang['mc_cotas_g3_settings'] = 'Configurações';

// Sincronização
$lang['mc_cotas_g3_sync_now'] = 'Sincronizar Agora';
$lang['mc_cotas_g3_sync_confirm'] = 'Tem certeza que deseja executar a sincronização agora? Este processo pode levar alguns minutos.';
$lang['mc_cotas_g3_sync_success'] = 'Sincronização concluída com sucesso! Total de membros: %s | Leads encontrados: %s | Leads atualizados: %s | Não encontrados: %s | Erros: %s';
$lang['mc_cotas_g3_sync_error'] = 'Erro durante a sincronização';
$lang['mc_cotas_g3_sync_interval_error'] = 'Você precisa aguardar %s hora(s) e %s minuto(s) para sincronizar novamente. Intervalo mínimo: 6 horas.';
$lang['mc_cotas_g3_sync_history'] = 'Histórico de Sincronizações';
$lang['mc_cotas_g3_no_sync_history'] = 'Nenhuma sincronização realizada ainda.';
$lang['mc_cotas_g3_sync_log_details'] = 'Detalhes da Sincronização';

// Estatísticas
$lang['mc_cotas_g3_total_synced'] = 'Total Sincronizado';
$lang['mc_cotas_g3_total_titular'] = 'Titulares';
$lang['mc_cotas_g3_total_dependente'] = 'Dependentes';
$lang['mc_cotas_g3_last_sync'] = 'Última Sincronização';
$lang['mc_cotas_g3_never_synced'] = 'Nunca';

// Tabela de Histórico
$lang['mc_cotas_g3_sync_date'] = 'Data/Hora';
$lang['mc_cotas_g3_total_members'] = 'Total de Membros';
$lang['mc_cotas_g3_new_leads'] = 'Leads Encontrados';
$lang['mc_cotas_g3_updated_leads'] = 'Leads Atualizados';
$lang['mc_cotas_g3_errors'] = 'Erros';
$lang['mc_cotas_g3_execution_time'] = 'Tempo de Execução';
$lang['mc_cotas_g3_sync_by'] = 'Sincronizado Por';

// Configurações - Conexão SQL Server
$lang['mc_cotas_g3_connection_settings'] = 'Configurações de Conexão SQL Server';
$lang['mc_cotas_g3_connection_settings_desc'] = 'Configure as credenciais de acesso ao banco de dados SQL Server do Multiclubes.';
$lang['mc_cotas_g3_sqlserver_host'] = 'Host do SQL Server';
$lang['mc_cotas_g3_sqlserver_port'] = 'Porta';
$lang['mc_cotas_g3_sqlserver_database'] = 'Banco de Dados';
$lang['mc_cotas_g3_sqlserver_user'] = 'Usuário';
$lang['mc_cotas_g3_sqlserver_password'] = 'Senha';
$lang['mc_cotas_g3_test_connection'] = 'Testar Conexão';
$lang['mc_cotas_g3_testing_connection'] = 'Testando conexão...';
$lang['mc_cotas_g3_connection_success'] = 'Conexão estabelecida com sucesso!';
$lang['mc_cotas_g3_connection_error'] = 'Erro ao conectar ao SQL Server. Verifique as credenciais.';

// Configurações - Sincronização
$lang['mc_cotas_g3_sync_settings'] = 'Configurações de Sincronização';
$lang['mc_cotas_g3_sync_settings_desc'] = 'Configure o comportamento da sincronização automática e filtros.';
$lang['mc_cotas_g3_auto_sync'] = 'Sincronização Automática (via CRON)';
$lang['mc_cotas_g3_auto_sync_help'] = 'Ativar sincronização automática através do CRON do sistema.';
$lang['mc_cotas_g3_sync_interval'] = 'Intervalo de Sincronização (horas)';
$lang['mc_cotas_g3_sync_interval_help'] = 'Intervalo em horas entre cada sincronização automática.';
$lang['mc_cotas_g3_sync_only_titular'] = 'Sincronizar Apenas Titulares';
$lang['mc_cotas_g3_sync_only_titular_help'] = 'Se ativado, apenas membros titulares serão sincronizados.';
$lang['mc_cotas_g3_sync_only_active'] = 'Sincronizar Apenas Membros Ativos';
$lang['mc_cotas_g3_sync_only_active_help'] = 'Se ativado, apenas membros com status "Ativo" serão sincronizados.';
$lang['mc_cotas_g3_sync_batch_size'] = 'Tamanho do Lote (Batch)';
$lang['mc_cotas_g3_sync_batch_size_help'] = 'Quantidade de membros processados por vez. Valores menores usam menos memória mas levam mais tempo. Recomendado: 100-500';

// Configurações - Mapeamento
$lang['mc_cotas_g3_mapping_settings'] = 'Configurações de Mapeamento';
$lang['mc_cotas_g3_mapping_settings_desc'] = 'Configure como os membros do Multiclubes serão comparados com os leads no MyLeads CRM.';
$lang['mc_cotas_g3_default_status'] = 'Status Padrão dos Leads';
$lang['mc_cotas_g3_default_status_help'] = 'Status que será atribuído aos novos leads criados.';
$lang['mc_cotas_g3_default_source'] = 'Fonte Padrão dos Leads';
$lang['mc_cotas_g3_default_source_help'] = 'Fonte que será atribuída aos novos leads criados.';
$lang['mc_cotas_g3_default_assigned'] = 'Atribuir Automaticamente Para';
$lang['mc_cotas_g3_default_assigned_help'] = 'Membro da equipe que receberá automaticamente os novos leads. Deixe em branco para não atribuir.';
$lang['mc_cotas_g3_not_assigned'] = 'Não atribuir';

// Configurações - Log
$lang['mc_cotas_g3_log_settings'] = 'Configurações de Log';
$lang['mc_cotas_g3_enable_detailed_log'] = 'Habilitar Log Detalhado';
$lang['mc_cotas_g3_enable_detailed_log_help'] = 'Se ativado, registrará cada ação no log de atividades do sistema.';

// Detalhes do Log
$lang['mc_cotas_g3_general_info'] = 'Informações Gerais';
$lang['mc_cotas_g3_error_details'] = 'Detalhes dos Erros';
$lang['mc_cotas_g3_member_id'] = 'ID do Membro';
$lang['mc_cotas_g3_member_name'] = 'Nome do Membro';
$lang['mc_cotas_g3_error_message'] = 'Mensagem de Erro';

// Histórico
$lang['mc_cotas_g3_history_cleared'] = 'Histórico de sincronização limpo com sucesso!';
$lang['mc_cotas_g3_no_history_to_clear'] = 'Não há histórico para limpar.';

// Mensagens Gerais
$lang['back'] = 'Voltar';
$lang['system'] = 'Sistema';
$lang['settings_save'] = 'Salvar Configurações';
$lang['settings_updated'] = 'Configurações atualizadas com sucesso!';
