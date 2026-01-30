#!/bin/bash

###############################################################################
# Script de Instalação Automática - Drivers SQL Server para PHP
# Módulo MC Cotas G3 - MyLeads CRM
###############################################################################

set -e

echo "=========================================="
echo "  MC Cotas G3 - Instalador de Drivers"
echo "  Microsoft SQL Server para PHP 8.4"
echo "=========================================="
echo ""

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Por favor, execute como root ou com sudo"
    echo "   Exemplo: sudo bash install_drivers.sh"
    exit 1
fi

# Detectar sistema operacional
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    OS_VERSION=$VERSION_ID
else
    echo "❌ Não foi possível detectar o sistema operacional"
    exit 1
fi

echo "📋 Sistema detectado: $OS $OS_VERSION"
echo ""

# Confirmar instalação
read -p "Deseja continuar com a instalação? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[SsYy]$ ]]; then
    echo "Instalação cancelada."
    exit 0
fi

echo ""
echo "🔄 Atualizando sistema..."
apt-get update -qq

echo "📦 Instalando dependências..."
apt-get install -y curl apt-transport-https gnupg unixodbc-dev php8.4-dev php-pear build-essential > /dev/null 2>&1

echo "🔑 Adicionando repositório Microsoft..."
curl -sSL https://packages.microsoft.com/keys/microsoft.asc | tee /etc/apt/trusted.gpg.d/microsoft.asc > /dev/null

if [ "$OS" = "debian" ]; then
    curl -sSL https://packages.microsoft.com/config/debian/12/prod.list | tee /etc/apt/sources.list.d/mssql-release.list > /dev/null
elif [ "$OS" = "ubuntu" ]; then
    curl -sSL https://packages.microsoft.com/config/ubuntu/22.04/prod.list | tee /etc/apt/sources.list.d/mssql-release.list > /dev/null
else
    echo "⚠️ Sistema operacional não suportado diretamente: $OS"
    echo "   Tentando instalação genérica..."
fi

echo "📦 Instalando Microsoft ODBC Driver 18..."
apt-get update -qq
ACCEPT_EULA=Y apt-get install -y msodbcsql18 > /dev/null 2>&1

echo "🔧 Instalando extensões PHP via PECL..."
pecl channel-update pecl.php.net > /dev/null 2>&1

# Verificar se já está instalado
if php -m | grep -q sqlsrv; then
    echo "ℹ️ Extensão sqlsrv já instalada, pulando..."
else
    echo "   Instalando sqlsrv..."
    printf "\n" | pecl install sqlsrv > /dev/null 2>&1 || echo "⚠️ Aviso ao instalar sqlsrv"
fi

if php -m | grep -q pdo_sqlsrv; then
    echo "ℹ️ Extensão pdo_sqlsrv já instalada, pulando..."
else
    echo "   Instalando pdo_sqlsrv..."
    printf "\n" | pecl install pdo_sqlsrv > /dev/null 2>&1 || echo "⚠️ Aviso ao instalar pdo_sqlsrv"
fi

echo "⚙️ Configurando extensões PHP..."

# Criar arquivos de configuração se não existirem
if [ ! -f /etc/php/8.4/mods-available/sqlsrv.ini ]; then
    echo "extension=sqlsrv.so" > /etc/php/8.4/mods-available/sqlsrv.ini
fi

if [ ! -f /etc/php/8.4/mods-available/pdo_sqlsrv.ini ]; then
    echo "extension=pdo_sqlsrv.so" > /etc/php/8.4/mods-available/pdo_sqlsrv.ini
fi

# Habilitar extensões
phpenmod -v 8.4 sqlsrv 2>/dev/null || true
phpenmod -v 8.4 pdo_sqlsrv 2>/dev/null || true

echo "🔄 Reiniciando serviços web..."

# Reiniciar PHP-FPM
if systemctl is-active --quiet php8.4-fpm; then
    systemctl restart php8.4-fpm
    echo "   ✅ PHP-FPM reiniciado"
fi

# Reiniciar Apache
if systemctl is-active --quiet apache2; then
    systemctl restart apache2
    echo "   ✅ Apache reiniciado"
fi

# Reiniciar Nginx
if systemctl is-active --quiet nginx; then
    systemctl restart nginx
    echo "   ✅ Nginx reiniciado"
fi

echo ""
echo "✅ Instalação concluída!"
echo ""
echo "🔍 Verificando instalação..."
echo ""

# Verificar extensões instaladas
if php -m | grep -q sqlsrv && php -m | grep -q pdo_sqlsrv; then
    echo "✅ Extensões instaladas com sucesso:"
    php -m | grep sqlsrv
    echo ""
    echo "=========================================="
    echo "  PRÓXIMOS PASSOS"
    echo "=========================================="
    echo ""
    echo "1. Acesse o MyLeads CRM"
    echo "2. Vá para: MC Cotas G3 → Configurações"
    echo "3. Clique em 'Testar Conexão'"
    echo "4. Verifique se aparece: ✅ Conexão bem-sucedida!"
    echo ""
else
    echo "⚠️ Algo deu errado. Verifique os logs:"
    echo ""
    echo "   tail -f /var/log/php8.4-fpm.log"
    echo "   php -m | grep sql"
    echo ""
    echo "Consulte: INSTALL_SQLSERVER_DRIVERS.md"
fi

echo ""
