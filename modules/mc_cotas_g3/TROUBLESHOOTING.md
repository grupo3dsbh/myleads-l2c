# TROUBLESHOOTING - MC Cotas G3

## Erro 500 ao ativar o módulo

Se você está recebendo erro 500 ao tentar ativar o módulo, siga este guia passo a passo.

---

## SOLUÇÃO 1: Verificar o log de instalação

### Passo 1: Tentar ativar o módulo

1. Acesse **Setup → Módulos**
2. Clique em **Ativar** no módulo MC Cotas G3
3. Aguarde (pode dar erro 500)

### Passo 2: Verificar o arquivo de log

O install.php agora gera um log detalhado. Verifique o arquivo:

```
uploads/mc_cotas_g3_install.log
```

**Como acessar:**
- Via FTP: baixe o arquivo
- Via cPanel File Manager: navegue até a pasta `uploads/`
- Via terminal SSH: `cat uploads/mc_cotas_g3_install.log`

O arquivo mostrará EXATAMENTE onde o erro ocorreu.

### Passo 3: Enviar o log

Envie o conteúdo do arquivo `mc_cotas_g3_install.log` para análise.

---

## SOLUÇÃO 2: Usar instalação minimalista

Se o install.php completo está dando erro, use a versão minimalista:

### Passo 1: Fazer backup do install.php atual

Via FTP ou terminal:
```bash
cd modules/mc_cotas_g3/
mv install.php install_backup.php
```

### Passo 2: Renomear o install_minimal.php

```bash
mv install_minimal.php install.php
```

### Passo 3: Tentar ativar novamente

1. Acesse **Setup → Módulos**
2. Tente ativar o módulo
3. Verifique o log em `uploads/mc_minimal.log`

---

## SOLUÇÃO 3: Instalar manualmente via SQL

Se nenhuma das soluções acima funcionar, instale manualmente executando os SQLs:

### Passo 1: Acessar phpMyAdmin

1. Acesse o phpMyAdmin do seu servidor
2. Selecione o banco de dados do Perfex CRM

### Passo 2: Executar SQLs de criação de tabelas

**IMPORTANTE: Substitua `aquamais_` pelo prefixo do seu banco!**

```sql
-- Tabela de sincronização
CREATE TABLE `aquamais_mc_cotas_g3_sync` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de log
CREATE TABLE `aquamais_mc_cotas_g3_sync_log` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adicionar campos na tabela de leads
ALTER TABLE `aquamais_leads` ADD `mc_member_id` INT(11) NULL;
ALTER TABLE `aquamais_leads` ADD `mc_title_code` VARCHAR(50) NULL;
ALTER TABLE `aquamais_leads` ADD `mc_is_titular` TINYINT(1) DEFAULT 0;

-- Adicionar índice único (execute separadamente se der erro)
ALTER TABLE `aquamais_leads` ADD UNIQUE KEY `mc_member_id` (`mc_member_id`);

-- Inserir opções
INSERT INTO `aquamais_options` (`name`, `value`, `autoload`) VALUES
('mc_cotas_g3_sqlserver_host', 'aquamais.cloud.multiclubes.com.br', 1),
('mc_cotas_g3_sqlserver_user', 'biaquamais', 1),
('mc_cotas_g3_sqlserver_password', 'sPuu1XQhHMy@', 1),
('mc_cotas_g3_sqlserver_database', 'MultiClubes', 1),
('mc_cotas_g3_sqlserver_port', '1433', 1),
('mc_cotas_g3_auto_sync', '0', 1),
('mc_cotas_g3_sync_interval', '24', 1),
('mc_cotas_g3_sync_only_titular', '1', 1),
('mc_cotas_g3_sync_only_active', '1', 1),
('mc_cotas_g3_default_status', '3', 1),
('mc_cotas_g3_default_source', '8', 1),
('mc_cotas_g3_default_assigned', '0', 1),
('mc_cotas_g3_enable_detailed_log', '1', 1);

-- Inserir permissões (substitua 1 pelo ID do seu papel de administrador se for diferente)
INSERT INTO `aquamais_permissions` (`permissionid`, `feature`, `capability`) VALUES
(1, 'mc_cotas_g3', 'view'),
(1, 'mc_cotas_g3', 'create'),
(1, 'mc_cotas_g3', 'edit'),
(1, 'mc_cotas_g3', 'delete');
```

### Passo 3: Após executar os SQLs

1. Volte para **Setup → Módulos**
2. O módulo deverá aparecer como **Ativo**
3. Acesse **MC Cotas G3 → Configurações**

---

## SOLUÇÃO 4: Verificar logs do servidor

### Apache
```bash
tail -f /var/log/apache2/error.log
```

### Nginx
```bash
tail -f /var/log/nginx/error.log
```

### PHP-FPM
```bash
tail -f /var/log/php7.4-fpm.log
```

---

## PROBLEMAS COMUNS

### 1. Tabela `tblleads` não encontrada

**Sintoma:** Erro ao adicionar campos
**Solução:** Verifique se a tabela existe com o prefixo correto

```sql
SHOW TABLES LIKE '%leads';
```

### 2. Permissões de arquivo

**Sintoma:** Erro ao escrever no log
**Solução:** Dar permissões corretas

```bash
chmod 755 modules/mc_cotas_g3/
chmod 777 uploads/
```

### 3. Memória insuficiente

**Sintoma:** Erro 500 sem log
**Solução:** Aumentar memory_limit no php.ini

```ini
memory_limit = 256M
```

### 4. Timeout de execução

**Sintoma:** Página carrega e dá timeout
**Solução:** Aumentar max_execution_time

```ini
max_execution_time = 300
```

---

## AINDA COM PROBLEMAS?

Se nenhuma solução funcionou:

1. ✅ Envie o conteúdo de `uploads/mc_cotas_g3_install.log`
2. ✅ Envie o conteúdo do log de erro do servidor (Apache/Nginx)
3. ✅ Informe a versão do PHP: `php -v`
4. ✅ Informe a versão do Perfex CRM
5. ✅ Faça um print da tela de módulos mostrando o erro

---

## DESINSTALAR COMPLETAMENTE

Se quiser remover tudo e começar do zero:

```sql
-- Remover tabelas
DROP TABLE IF EXISTS `aquamais_mc_cotas_g3_sync`;
DROP TABLE IF EXISTS `aquamais_mc_cotas_g3_sync_log`;

-- Remover campos (opcional)
ALTER TABLE `aquamais_leads` DROP COLUMN `mc_member_id`;
ALTER TABLE `aquamais_leads` DROP COLUMN `mc_title_code`;
ALTER TABLE `aquamais_leads` DROP COLUMN `mc_is_titular`;

-- Remover opções
DELETE FROM `aquamais_options` WHERE `name` LIKE 'mc_cotas_g3%';

-- Remover permissões
DELETE FROM `aquamais_permissions` WHERE `feature` = 'mc_cotas_g3';
```

Depois:
1. Remova a pasta `modules/mc_cotas_g3/`
2. Faça upload novamente
3. Tente ativar
