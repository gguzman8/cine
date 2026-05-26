#!/usr/bin/env bash
#
# Script: setup.sh
# Descripción: Provisiona todo el servidor Cine desde cero.
#             Instala LAMP, configura BD, firewall, scripts, etc.
#
# Uso: sudo bash setup.sh
#

set -euo pipefail

LOG="/var/log/cine_error.log"
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "============================================"
echo "  Provisioning — Cine (LAMP + Bash + UFW)"
echo "============================================"

# ─── Verificar root ──────────────────────────────
if [ "$EUID" -ne 0 ]; then
    echo "Error: Ejecuta con sudo."
    exit 1
fi

# ─── 1. Instalar LAMP ────────────────────────────
echo "[1/8] Instalando Apache, PHP, MariaDB/MySQL..."
apt-get update -y
apt-get install -y apache2 php libapache2-mod-php php-mysql \
    php-curl php-mbstring php-xml mariadb-server mariadb-client \
    ufw unzip

# ─── 2. Configurar MySQL ─────────────────────────
echo "[2/8] Configurando base de datos..."
if ! mysql -e "SELECT 1" &>/dev/null; then
    echo "Iniciando MySQL..."
    systemctl start mysql
fi

DB_USER="cine_user"
DB_PASS="cine_pass"

mysql <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS cine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON cine.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

echo "   Importando schema.sql..."
mysql -u "$DB_USER" -p"$DB_PASS" cine < "${PROJECT_DIR}/sql/schema.sql"

# Sembrar usuarios con hash bcrypt correcto
echo "   Sembrando usuarios iniciales..."
php "${PROJECT_DIR}/src/seed.php"

echo "   Base de datos lista."

# ─── 3. Configurar Apache ────────────────────────
echo "[3/8] Configurando Apache..."
cat > /etc/apache2/sites-available/cine.conf <<APACHE
<VirtualHost *:80>
    ServerName cine.local
    DocumentRoot ${PROJECT_DIR}/public

    <Directory ${PROJECT_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Alias /src ${PROJECT_DIR}/src
    <Directory ${PROJECT_DIR}/src>
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/cine_error.log
    CustomLog \${APACHE_LOG_DIR}/cine_access.log combined
</VirtualHost>
APACHE

a2dissite 000-default.conf 2>/dev/null || true
a2ensite cine.conf
a2enmod rewrite
systemctl restart apache2

# ─── 4. Configurar UFW ───────────────────────────
echo "[4/8] Configurando firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow 80/tcp comment "Web (HTTP)"
ufw allow 2222/tcp comment "SSH personalizado"
ufw limit 2222/tcp comment "Rate-limit SSH"
ufw --force enable

# ─── 5. Copiar scripts ─────────────────────────
echo "[5/8] Instalando scripts..."
cp "${PROJECT_DIR}/scripts/watchdog.sh" /usr/local/bin/watchdog.sh
cp "${PROJECT_DIR}/scripts/respaldo.sh" /usr/local/bin/respaldo.sh
cp "${PROJECT_DIR}/scripts/gestion_staff.sh" /usr/local/bin/gestion_staff.sh
chmod +x /usr/local/bin/watchdog.sh /usr/local/bin/respaldo.sh /usr/local/bin/gestion_staff.sh

# ─── 6. Servicio systemd para watchdog ─────────
echo "[6/8] Instalando servicio watchdog..."
cat > /etc/systemd/system/cine-watchdog.service <<UNIT
[Unit]
Description=Cine Watchdog — Monitorea Apache y MySQL
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/watchdog.sh
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable cine-watchdog
systemctl start cine-watchdog

# ─── 7. Crear directorio de backups ────────────
echo "[7/8] Creando /var/backups/cine..."
mkdir -p /var/backups/cine
chmod 750 /var/backups/cine

# Agregar respaldo.sh al cron diario
cp "${PROJECT_DIR}/scripts/respaldo.sh" /etc/cron.daily/cine-respaldo 2>/dev/null || true
chmod +x /etc/cron.daily/cine-respaldo 2>/dev/null || true

# ─── 8. Log inicial ────────────────────────────
echo "[8/8] Finalizado."
echo "[$(date '+%Y-%m-%d %H:%M:%S')] SETUP: Provisioning completado." >> "$LOG"

echo ""
echo "============================================"
echo "  ✅ Cine instalado correctamente"
echo "============================================"
echo "  Web:      http://localhost"
echo "  SSH:      puerto 2222"
echo "  Backups:  /var/backups/cine/"
echo "  Logs:     ${LOG}"
echo "============================================"
echo "⚠️  Cambia las contraseñas por defecto en:"
echo "   - setup.sh (DB_PASS)"
echo "   - scripts/respaldo.sh (DB_PASS)"
echo "⚠️  Configura tu SSH para usar puerto 2222"
echo "============================================"
