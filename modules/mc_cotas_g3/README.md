# MC Cotas G3 - Módulo MyLeads CRM

## Descrição

Módulo para integração do **Multiclubes** com o **MyLeads** (MyLeads CRM). Sincroniza automaticamente os membros/cotas vendidas do Multiclubes como leads no MyLeads CRM.

## Características

- ✅ Conexão segura com SQL Server do Multiclubes
- ✅ Sincronização manual ou automática (CRON)
- ✅ Mapeamento inteligente de campos
- ✅ Filtros configuráveis (titulares/dependentes, ativos/inativos)
- ✅ Histórico completo de sincronizações
- ✅ Log detalhado de erros
- ✅ Interface administrativa completa
- ✅ Suporte a múltiplos drivers SQL Server (SQLSRV e PDO_SQLSRV)

## Requisitos

### Sistema

- MyLeads CRM versão 2.3.0 ou superior
- PHP 7.2 ou superior
- MySQL/MariaDB

### Extensões PHP Necessárias

Uma das seguintes extensões deve estar instalada:

- **php-sqlsrv** (recomendado) - Microsoft SQL Server Driver for PHP
- **php-pdo_sqlsrv** - PDO Driver for SQL Server

#### Instalação das extensões no Ubuntu/Debian:

```bash
# Adicionar repositório Microsoft
curl https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -
curl https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list

# Instalar drivers
sudo apt-get update
sudo ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools
sudo apt-get install -y php-dev php-pear php-sqlsrv php-pdo-sqlsrv

# Reiniciar servidor web
sudo systemctl restart apache2
# ou
sudo systemctl restart php7.4-fpm
```

## Instalação

1. Faça upload da pasta `mc_cotas_g3` para o diretório `modules/` do MyLeads CRM

2. Acesse o MyLeads CRM como administrador

3. Navegue até **Setup → Módulos**

4. Encontre o módulo **MC Cotas G3** e clique em **Ativar**

5. Após ativação, configure as credenciais de acesso

## Configuração

### 1. Configurações de Conexão SQL Server

Acesse: **MC Cotas G3 → Configurações**

Configure os seguintes parâmetros:

- **Host do SQL Server:** `aquamais.cloud.multiclubes.com.br`
- **Porta:** `1433`
- **Banco de Dados:** `MultiClubes`
- **Usuário:** `biaquamais`
- **Senha:** `sPuu1XQhHMy@`

Clique em **Testar Conexão** para validar as credenciais.

### 2. Configurações de Sincronização

- **Sincronização Automática (CRON):** Ativar/desativar sincronização automática
- **Intervalo de Sincronização:** Intervalo em horas (padrão: 24h)
- **Sincronizar Apenas Titulares:** Sincronizar somente membros titulares
- **Sincronizar Apenas Membros Ativos:** Sincronizar somente membros com status "Ativo"

### 3. Configurações de Mapeamento

- **Status Padrão dos Leads:** Status que será atribuído aos novos leads
- **Fonte Padrão dos Leads:** Fonte de origem dos leads (padrão: CRM)
- **Atribuir Automaticamente Para:** Membro da equipe que receberá os leads

## Utilização

### Sincronização Manual

1. Acesse **MC Cotas G3 → Sincronização**
2. Clique em **Sincronizar Agora**
3. Aguarde o processamento
4. Visualize o resultado na página

### Sincronização Automática

A sincronização automática é executada através do CRON do MyLeads CRM.

Para ativar:

1. Acesse **MC Cotas G3 → Configurações**
2. Marque **Sincronização Automática (via CRON)**
3. Configure o **Intervalo de Sincronização**
4. Salve as configurações

O CRON do Perfex deve estar configurado no servidor:

```bash
*/5 * * * * php /path/to/perfex/index.php cron/index > /dev/null 2>&1
```

### Visualizar Histórico

1. Acesse **MC Cotas G3 → Sincronização**
2. Role até a seção **Histórico de Sincronizações**
3. Visualize todos os logs de sincronização
4. Clique no número de erros para ver detalhes

## Estrutura de Dados

### Tabelas Criadas

#### `{prefix}_mc_cotas_g3_sync`

Armazena o relacionamento entre membros do Multiclubes e leads do MyLeads CRM.

| Campo             | Tipo         | Descrição                          |
|-------------------|--------------|------------------------------------|
| id                | INT          | ID único                           |
| member_id         | INT          | ID do membro no Multiclubes        |
| lead_id           | INT          | ID do lead no MyLeads CRM           |
| title_code        | VARCHAR(50)  | Código do título                   |
| title_type_name   | VARCHAR(191) | Nome do tipo de título             |
| member_status     | VARCHAR(50)  | Status do membro                   |
| is_titular        | TINYINT(1)   | Se é titular ou dependente         |
| last_sync_date    | DATETIME     | Data da última sincronização       |
| dateadded         | DATETIME     | Data de criação do registro        |

#### `{prefix}_mc_cotas_g3_sync_log`

Armazena o histórico de todas as sincronizações.

| Campo            | Tipo          | Descrição                          |
|------------------|---------------|------------------------------------|
| id               | INT           | ID único                           |
| sync_date        | DATETIME      | Data/hora da sincronização         |
| total_members    | INT           | Total de membros encontrados       |
| new_leads        | INT           | Novos leads criados                |
| updated_leads    | INT           | Leads atualizados                  |
| errors           | INT           | Número de erros                    |
| error_log        | TEXT          | Log de erros (JSON)                |
| sync_by          | INT           | ID do staff que executou           |
| is_cron          | TINYINT(1)    | Se foi executado via CRON          |
| execution_time   | DECIMAL(10,2) | Tempo de execução em segundos      |

### Campos Adicionados em Leads

Os seguintes campos são adicionados à tabela `{prefix}_leads`:

- `mc_member_id` - ID do membro no Multiclubes
- `mc_title_code` - Código do título
- `mc_is_titular` - Se é titular ou dependente

## Mapeamento de Campos

### Multiclubes → MyLeads CRM

| Campo Multiclubes       | Campo MyLeads CRM | Observações                        |
|-------------------------|------------------|------------------------------------|
| MemberId                | mc_member_id     | Campo customizado                  |
| MemberName              | name             | Nome do lead                       |
| MemberEmail             | email            | E-mail do lead                     |
| MemberMobilePhone       | phonenumber      | Formatado com código do país       |
| AdressCity              | city             | Cidade                             |
| AdressState             | state            | Estado                             |
| AdressStreet + Number   | address          | Endereço completo                  |
| TitleCode               | mc_title_code    | Campo customizado                  |
| Titular                 | mc_is_titular    | Campo customizado (0 ou 1)         |
| Diversos                | description      | Informações formatadas em Markdown |

## Permissões

O módulo cria as seguintes permissões:

- **mc_cotas_g3 - view:** Visualizar sincronizações e configurações
- **mc_cotas_g3 - create:** Executar sincronização manual
- **mc_cotas_g3 - edit:** Editar configurações
- **mc_cotas_g3 - delete:** Limpar histórico

## Solução de Problemas

### ⚠️ Erro: "Nenhum driver SQL Server disponível (sqlsrv ou pdo_sqlsrv)"

**Este é o erro mais comum na primeira instalação.**

O servidor PHP não possui as extensões necessárias para conectar ao SQL Server. Siga os passos abaixo:

#### Solução Rápida (Instalação Automática):

```bash
# No servidor via SSH
cd /caminho/do/myleads/modules/mc_cotas_g3
sudo bash install_drivers.sh
```

O script irá:
1. ✅ Instalar Microsoft ODBC Driver 18 para SQL Server
2. ✅ Instalar extensões PHP (sqlsrv e pdo_sqlsrv)
3. ✅ Configurar e habilitar as extensões
4. ✅ Reiniciar serviços web (Apache/Nginx/PHP-FPM)

#### Solução Manual:

Consulte o guia completo: **[INSTALL_SQLSERVER_DRIVERS.md](INSTALL_SQLSERVER_DRIVERS.md)**

#### Verificar se foi instalado corretamente:

```bash
php -m | grep sqlsrv
# Deve retornar:
# pdo_sqlsrv
# sqlsrv
```

Após instalação, teste a conexão em: **MC Cotas G3 → Configurações → Testar Conexão**

---

### Erro ao conectar ao SQL Server

1. Verifique se as credenciais estão corretas
2. Verifique se a porta 1433 está liberada no firewall
3. Verifique se o servidor SQL Server permite conexões remotas
4. Teste a conexão usando o botão "Testar Conexão"

### Sincronização muito lenta

1. Aumente o `max_execution_time` no php.ini
2. Ative a sincronização apenas de titulares
3. Ative a sincronização apenas de membros ativos
4. Execute a sincronização em horários de menor uso

### Leads duplicados

O módulo previne duplicação usando o campo `mc_member_id` (único). Se houver duplicação:

1. Verifique se o campo foi criado corretamente
2. Verifique os logs de erro na sincronização

## Suporte

Para suporte técnico, entre em contato com:

**Grupo3 DSBH**
- Website: https://grupo3dsbh.com.br
- E-mail: suporte@grupo3dsbh.com.br

## Changelog

### Versão 1.0.0 (2026-01-11)

- ✅ Lançamento inicial
- ✅ Conexão com SQL Server do Multiclubes
- ✅ Sincronização manual e automática
- ✅ Interface administrativa completa
- ✅ Sistema de logs e histórico
- ✅ Configurações avançadas de mapeamento

## Licença

Propriedade de Grupo3 DSBH. Todos os direitos reservados.
