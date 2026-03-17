#!/bin/bash
# ============================================================
# Internet35 Billing - App Deployment Script
# Jalankan setelah upload source code ke server
# ============================================================

set -e

# ---- KONFIGURASI (UBAH SESUAI KEBUTUHAN) ----
DOMAIN="billing.domain-anda.com"
DB_NAME="internet35"
DB_USER="internet35"
DB_PASS="GANTI_PASSWORD_DATABASE_ANDA"
APP_DIR="/var/www/internet35"
# -----------------------------------------------

echo "============================================"
echo " Deploying Internet35 Billing..."
echo "============================================"

cd ${APP_DIR}

# 1. Set permissions
echo "[1/7] Setting permissions..."
chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 775 storage bootstrap/cache

# 2. Install PHP dependencies
echo "[2/7] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Install & build frontend
echo "[3/7] Building frontend assets..."
npm ci
npm run build

# 4. Create .env if not exists
if [ ! -f .env ]; then
    echo "[4/7] Creating .env..."
    cp .env.example .env

    # Generate app key
    php artisan key:generate --force

    # Update .env values
    sed -i "s|APP_ENV=local|APP_ENV=production|g" .env
    sed -i "s|APP_DEBUG=true|APP_DEBUG=false|g" .env
    sed -i "s|APP_URL=http://localhost:8000|APP_URL=https://${DOMAIN}|g" .env
    sed -i "s|DB_DATABASE=internet35|DB_DATABASE=${DB_NAME}|g" .env
    sed -i "s|DB_USERNAME=root|DB_USERNAME=${DB_USER}|g" .env
    sed -i "s|DB_PASSWORD=|DB_PASSWORD=${DB_PASS}|g" .env

    echo "  .env created. Review it before proceeding!"
else
    echo "[4/7] .env already exists, skipping..."
fi

# 5. Run migrations
echo "[5/7] Running migrations..."
php artisan migrate --force

# 6. Seed initial data (only if fresh)
if [ "$1" == "--seed" ]; then
    echo "[5b] Seeding database..."
    php artisan db:seed --force
fi

# 7. Optimize
echo "[6/7] Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# 8. Setup Scheduler cron
echo "[7/7] Setting up cron..."
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
(crontab -l -u www-data 2>/dev/null | grep -v "schedule:run"; echo "${CRON_LINE}") | crontab -u www-data -

# Setup Queue Worker as systemd service
cat > /etc/systemd/system/internet35-queue.service << EOF
[Unit]
Description=Internet35 Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now internet35-queue

echo ""
echo "============================================"
echo " Deployment complete!"
echo "============================================"
echo ""
echo " App URL : https://${DOMAIN}"
echo " Queue   : systemctl status internet35-queue"
echo " Logs    : tail -f ${APP_DIR}/storage/logs/laravel.log"
echo ""
echo " Next: setup Cloudflare Tunnel"
echo "   bash ${APP_DIR}/deploy/setup-tunnel.sh"
echo ""
