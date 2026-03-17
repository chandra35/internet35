#!/bin/bash
# ============================================================
# Internet35 Billing - Server Setup Script
# Untuk Ubuntu 22.04+ / Debian 12+ di Proxmox VM/CT
# ============================================================

set -e

# ---- KONFIGURASI (UBAH SESUAI KEBUTUHAN) ----
DOMAIN="billing.domain-anda.com"
DB_NAME="internet35"
DB_USER="internet35"
DB_PASS="GANTI_PASSWORD_DATABASE_ANDA"
APP_DIR="/var/www/internet35"
PHP_VERSION="8.3"
# -----------------------------------------------

echo "============================================"
echo " Internet35 Billing - Server Setup"
echo "============================================"

# 1. Update system
echo "[1/8] Updating system..."
apt update && apt upgrade -y

# 2. Install PHP & extensions
echo "[2/8] Installing PHP ${PHP_VERSION} & extensions..."
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-snmp \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-imagick \
    unzip curl git

# 3. Install Nginx
echo "[3/8] Installing Nginx..."
apt install -y nginx

# 4. Install MariaDB
echo "[4/8] Installing MariaDB..."
apt install -y mariadb-server
systemctl enable --now mariadb

# Create database & user
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
echo "  Database '${DB_NAME}' created with user '${DB_USER}'"

# 5. Install Composer
echo "[5/8] Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# 6. Install Node.js (for Vite build)
echo "[6/8] Installing Node.js 20..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
fi

# 7. Configure Nginx
echo "[7/8] Configuring Nginx..."
cat > /etc/nginx/sites-available/internet35 << 'NGINX_CONF'
server {
    listen 80;
    server_name DOMAIN_PLACEHOLDER;
    root APP_DIR_PLACEHOLDER/public;

    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Real IP from Cloudflare
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 131.0.72.0/22;
    real_ip_header CF-Connecting-IP;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/phpPHP_VERSION_PLACEHOLDER-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_CONF

# Replace placeholders
sed -i "s|DOMAIN_PLACEHOLDER|${DOMAIN}|g" /etc/nginx/sites-available/internet35
sed -i "s|APP_DIR_PLACEHOLDER|${APP_DIR}|g" /etc/nginx/sites-available/internet35
sed -i "s|PHP_VERSION_PLACEHOLDER|${PHP_VERSION}|g" /etc/nginx/sites-available/internet35

ln -sf /etc/nginx/sites-available/internet35 /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# 8. Install Cloudflare Tunnel (cloudflared)
echo "[8/8] Installing cloudflared..."
if ! command -v cloudflared &> /dev/null; then
    curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
    dpkg -i cloudflared.deb
    rm cloudflared.deb
fi

echo ""
echo "============================================"
echo " Server setup complete!"
echo "============================================"
echo ""
echo " Next steps:"
echo " 1. Upload app to ${APP_DIR}"
echo " 2. Run: bash ${APP_DIR}/deploy/deploy-app.sh"
echo " 3. Run: bash ${APP_DIR}/deploy/setup-tunnel.sh"
echo ""
