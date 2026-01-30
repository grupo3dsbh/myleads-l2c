# Guia de Instalação - Drivers SQL Server para PHP

## ⚠️ Erro: "Nenhum driver SQL Server disponível"

Este erro ocorre porque o servidor PHP não possui as extensões necessárias para conectar ao SQL Server.

---

## 📋 Pré-requisitos

- Acesso SSH ao servidor
- Permissões de root/sudo
- PHP 8.4 (já instalado)
- Sistema operacional Linux (Debian/Ubuntu)

---

## 🔧 Instalação - Debian/Ubuntu

### Passo 1: Instalar Microsoft ODBC Driver 18 para SQL Server

```bash
# Atualizar sistema
sudo apt-get update

# Instalar dependências
sudo apt-get install -y curl apt-transport-https gnupg

# Adicionar repositório Microsoft
curl https://packages.microsoft.com/keys/microsoft.asc | sudo tee /etc/apt/trusted.gpg.d/microsoft.asc

# Para Debian 12 (Bookworm)
curl https://packages.microsoft.com/config/debian/12/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list

# OU para Ubuntu 22.04
# curl https://packages.microsoft.com/config/ubuntu/22.04/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list

# Atualizar e instalar driver ODBC
sudo apt-get update
sudo ACCEPT_EULA=Y apt-get install -y msodbcsql18

# Instalar ferramentas opcionais (opcional)
sudo ACCEPT_EULA=Y apt-get install -y mssql-tools18
echo 'export PATH="$PATH:/opt/mssql-tools18/bin"' >> ~/.bashrc
source ~/.bashrc

# Instalar unixODBC
sudo apt-get install -y unixodbc-dev
```

### Passo 2: Instalar Extensões PHP (sqlsrv e pdo_sqlsrv)

```bash
# Instalar ferramentas de build
sudo apt-get install -y php8.4-dev php-pear build-essential

# Instalar extensões via PECL
sudo pecl channel-update pecl.php.net
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv

# Adicionar extensões ao PHP
echo "extension=sqlsrv.so" | sudo tee /etc/php/8.4/mods-available/sqlsrv.ini
echo "extension=pdo_sqlsrv.so" | sudo tee /etc/php/8.4/mods-available/pdo_sqlsrv.ini

# Habilitar extensões para CLI e FPM
sudo phpenmod -v 8.4 sqlsrv
sudo phpenmod -v 8.4 pdo_sqlsrv

# Se estiver usando Apache
sudo systemctl restart apache2

# Se estiver usando PHP-FPM + Nginx
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

### Passo 3: Verificar Instalação

```bash
# Verificar se extensões foram instaladas
php -m | grep sqlsrv

# Deve retornar:
# pdo_sqlsrv
# sqlsrv
```

### Passo 4: Testar Conexão no MyLeads CRM

1. Acesse: **MC Cotas G3 → Configurações**
2. Clique em **"Testar Conexão"**
3. Deve retornar: ✅ "Conexão estabelecida com sucesso!"

---

## 🐳 Instalação via Docker (Alternativa)

Se o servidor estiver usando Docker, adicione ao `Dockerfile`:

```dockerfile
FROM php:8.4-fpm

# Instalar dependências
RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    unixodbc-dev

# Instalar Microsoft ODBC Driver
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18

# Instalar extensões PHP
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Rebuild e restart containers
```

---

## 🔧 Solução de Problemas

### Erro: "Unable to locate package msodbcsql18"

```bash
# Verificar se repositório foi adicionado corretamente
ls -la /etc/apt/sources.list.d/

# Verificar versão do Debian/Ubuntu
lsb_release -a

# Ajustar URL do repositório para sua versão específica
```

### Erro: "No package 'odbc' found" ao instalar PECL

```bash
# Instalar unixodbc-dev primeiro
sudo apt-get install -y unixodbc-dev
```

### Erro de compilação PECL

```bash
# Verificar se php-dev está instalado
sudo apt-get install -y php8.4-dev build-essential

# Limpar cache PECL e tentar novamente
sudo pecl clear-cache
sudo pecl install sqlsrv
```

### Extensões instaladas mas não aparecem

```bash
# Verificar se arquivos .so foram criados
ls -la /usr/lib/php/*/sqlsrv.so
ls -la /usr/lib/php/*/pdo_sqlsrv.so

# Verificar se .ini foi criado
ls -la /etc/php/8.4/mods-available/ | grep sqlsrv

# Habilitar manualmente
sudo ln -s /etc/php/8.4/mods-available/sqlsrv.ini /etc/php/8.4/cli/conf.d/20-sqlsrv.ini
sudo ln -s /etc/php/8.4/mods-available/pdo_sqlsrv.ini /etc/php/8.4/cli/conf.d/20-pdo_sqlsrv.ini
```

### Testar conexão via linha de comando

```bash
# Criar arquivo test.php
cat > /tmp/test_sqlserver.php << 'EOF'
<?php
$server = "aquamais.cloud.multiclubes.com.br,1433";
$database = "MultiClubes";
$username = "biaquamais";
$password = "SUA_SENHA_AQUI";

try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$database", $username, $password);
    echo "✅ Conexão bem-sucedida!\n";
    $conn = null;
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
EOF

# Executar teste
php /tmp/test_sqlserver.php
```

---

## 📚 Referências

- [Microsoft Drivers for PHP for SQL Server](https://learn.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server)
- [Installing ODBC Driver for SQL Server](https://learn.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server)
- [PECL sqlsrv](https://pecl.php.net/package/sqlsrv)

---

## 🆘 Suporte

Se continuar com problemas:

1. Verifique logs do PHP: `/var/log/php8.4-fpm.log`
2. Verifique logs do Apache/Nginx: `/var/log/nginx/error.log`
3. Execute: `php -i | grep sql` para ver configurações SQL
4. Entre em contato com o administrador do servidor

---

## ✅ Checklist de Instalação

- [ ] Microsoft ODBC Driver 18 instalado
- [ ] unixodbc-dev instalado
- [ ] Extensão sqlsrv instalada via PECL
- [ ] Extensão pdo_sqlsrv instalada via PECL
- [ ] Extensões habilitadas no PHP
- [ ] Servidor web reiniciado
- [ ] Teste de conexão bem-sucedido
