#!/bin/bash
# ============================================================
# Internet35 - Shared Hosting Deployment Script
# Target: /home/manmetr1/wifi35.net (cPanel shared hosting)
# ============================================================
#
# STRUKTUR DI SHARED HOSTING:
#   /home/manmetr1/
#   ├── wifi35.net/          ← Document root (= Laravel /public)
#   │   ├── index.php        ← Modified Laravel entry point
#   │   ├── .htaccess
#   │   ├── build/           ← Vite build assets
#   │   ├── assets/          ← Static assets
#   │   └── storage/         ← Symlink ke ../internet35-app/storage/app/public
#   │
#   └── internet35-app/      ← Laravel app (di ATAS document root)
#       ├── app/
#       ├── bootstrap/
#       ├── config/
#       ├── database/
#       ├── resources/
#       ├── routes/
#       ├── storage/
#       ├── vendor/
#       ├── .env
#       └── ...
#
# CARA PAKAI:
#   1. Upload internet35-app.zip ke /home/manmetr1/ via File Manager / SFTP
#   2. SSH ke server, jalankan script ini
#   3. Atau extract manual dan ikuti langkah di bawah
#
# ============================================================

set -e

HOME_DIR="/home/manmetr1"
APP_DIR="${HOME_DIR}/internet35-app"
PUBLIC_DIR="${HOME_DIR}/wifi35.net"
DOMAIN="wifi35.net"
DB_NAME="manmetr1_internet35"    # sesuaikan dengan cPanel DB name
DB_USER="manmetr1_internet35"    # sesuaikan dengan cPanel DB user
DB_PASS="GANTI_PASSWORD_ANDA"    # sesuaikan

echo "============================================"
echo " Internet35 - Shared Hosting Deploy"
echo "============================================"

# 1. Extract app
if [ -f "${HOME_DIR}/internet35-app.zip" ]; then
    echo "[1] Extracting app..."
    cd ${HOME_DIR}
    unzip -o internet35-app.zip -d internet35-app
else
    echo "[1] internet35-app.zip not found, assuming already extracted"
fi

cd ${APP_DIR}

# 2. Install composer dependencies
echo "[2] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Setup .env
if [ ! -f .env ]; then
    echo "[3] Creating .env..."
    cp .env.example .env
    php artisan key:generate --force
else
    echo "[3] .env exists, skipping..."
fi

# Update .env
echo "[3b] Updating .env..."
sed -i "s|APP_ENV=local|APP_ENV=production|g" .env
sed -i "s|APP_DEBUG=true|APP_DEBUG=false|g" .env
sed -i "s|APP_URL=http://localhost:8000|APP_URL=https://${DOMAIN}|g" .env
sed -i "s|DB_CONNECTION=sqlite|DB_CONNECTION=mysql|g" .env
sed -i "s|# DB_HOST=127.0.0.1|DB_HOST=127.0.0.1|g" .env
sed -i "s|# DB_PORT=3306|DB_PORT=3306|g" .env
sed -i "s|# DB_DATABASE=laravel|DB_DATABASE=${DB_NAME}|g" .env
sed -i "s|# DB_USERNAME=root|DB_USERNAME=${DB_USER}|g" .env
sed -i "s|# DB_PASSWORD=|DB_PASSWORD=${DB_PASS}|g" .env
sed -i "s|DB_DATABASE=internet35|DB_DATABASE=${DB_NAME}|g" .env
sed -i "s|DB_USERNAME=root|DB_USERNAME=${DB_USER}|g" .env
sed -i "s|DB_PASSWORD=|DB_PASSWORD=${DB_PASS}|g" .env

# 4. Run migrations
echo "[4] Running migrations..."
php artisan migrate --force

# 5. Seed (first time only, uncomment if needed)
# echo "[5] Seeding..."
# php artisan db:seed --force

# 6. Move public files to document root
echo "[6] Setting up public directory..."

# Backup existing files in document root
if [ -d "${PUBLIC_DIR}" ] && [ ! -f "${PUBLIC_DIR}/.laravel-deployed" ]; then
    echo "  Backing up existing wifi35.net files..."
    mkdir -p ${HOME_DIR}/wifi35.net.bak
    cp -r ${PUBLIC_DIR}/* ${HOME_DIR}/wifi35.net.bak/ 2>/dev/null || true
fi

# Copy public files
cp -r ${APP_DIR}/public/* ${PUBLIC_DIR}/
cp ${APP_DIR}/public/.htaccess ${PUBLIC_DIR}/ 2>/dev/null || true

# Create modified index.php
cat > ${PUBLIC_DIR}/index.php << 'PHPEOF'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Path to the Laravel application (above document root)
$appPath = dirname(__DIR__) . '/internet35-app';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once $appPath . '/bootstrap/app.php')
    ->handleRequest(Request::capture());
PHPEOF

# 7. Storage symlink
echo "[7] Creating storage symlink..."
rm -f ${PUBLIC_DIR}/storage
ln -sf ${APP_DIR}/storage/app/public ${PUBLIC_DIR}/storage

# 8. Permissions
echo "[8] Setting permissions..."
chmod -R 755 ${APP_DIR}
chmod -R 775 ${APP_DIR}/storage
chmod -R 775 ${APP_DIR}/bootstrap/cache

# 9. Cache
echo "[9] Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Mark as deployed
touch ${PUBLIC_DIR}/.laravel-deployed

echo ""
echo "============================================"
echo " Deployment complete!"
echo "============================================"
echo ""
echo " URL: https://${DOMAIN}"
echo " App: ${APP_DIR}"
echo " Web: ${PUBLIC_DIR}"
echo ""
echo " PENTING: Setup cron job di cPanel:"
echo "   * * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
echo ""
