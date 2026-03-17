#!/bin/bash
# ============================================================
# Internet35 - Deploy to Shared Hosting (wifi35.net)
# 
# Jalankan di server via SSH:
#   ssh manmetr1@SERVER_IP
#   bash deploy-wifi35.sh
#
# STRUKTUR:
#   /home/manmetr1/
#   ├── wifi35.net/          ← Document root (public)
#   │   ├── index.php        ← Modified entry point
#   │   ├── .htaccess
#   │   ├── build/           ← Vite assets  
#   │   └── storage -> ../internet35-app/storage/app/public
#   └── internet35-app/      ← Laravel app (above webroot)
# ============================================================

set -e

HOME_DIR="/home/manmetr1"
APP_DIR="${HOME_DIR}/internet35-app"
PUBLIC_DIR="${HOME_DIR}/wifi35.net"
REPO="https://github.com/chandra35/internet35.git"

echo "============================================"
echo " Deploy Internet35 to wifi35.net"
echo "============================================"

# ---- STEP 1: Clone / Pull repository ----
echo ""
echo "[1/8] Getting source code..."
if [ -d "${APP_DIR}/.git" ]; then
    echo "  Repository exists, pulling latest..."
    cd ${APP_DIR}
    git pull origin main
else
    echo "  Cloning repository..."
    git clone ${REPO} ${APP_DIR}
    cd ${APP_DIR}
fi

# ---- STEP 2: Install Composer dependencies ----
echo ""
echo "[2/8] Installing Composer dependencies..."
cd ${APP_DIR}
composer install --no-dev --optimize-autoloader --no-interaction 2>&1

# ---- STEP 3: Build frontend (jika ada npm) ----
echo ""
echo "[3/8] Building frontend..."
if command -v npm &> /dev/null; then
    npm ci --production=false 2>&1
    npx vite build 2>&1
else
    echo "  npm not available. Checking if build assets exist..."
    if [ -d "${APP_DIR}/public/build" ]; then
        echo "  Build assets found in repo, OK."
    else
        echo "  WARNING: No npm and no build assets! Frontend might not work."
        echo "  Build locally and upload public/build/ manually."
    fi
fi

# ---- STEP 4: Setup .env ----
echo ""
echo "[4/8] Setting up .env..."
if [ ! -f ${APP_DIR}/.env ]; then
    cp ${APP_DIR}/.env.example ${APP_DIR}/.env
    
    # Generate app key
    cd ${APP_DIR}
    php artisan key:generate --force
    
    echo ""
    echo "  ╔══════════════════════════════════════════════╗"
    echo "  ║  .env BARU DIBUAT! Edit dulu sebelum lanjut ║"
    echo "  ╚══════════════════════════════════════════════╝"
    echo ""
    echo "  Edit ${APP_DIR}/.env dan isi:"
    echo "    APP_ENV=production"
    echo "    APP_DEBUG=false"
    echo "    APP_URL=https://wifi35.net"
    echo ""
    echo "    DB_CONNECTION=mysql"
    echo "    DB_HOST=localhost"
    echo "    DB_PORT=3306"
    echo "    DB_DATABASE=manmetr1_internet35"
    echo "    DB_USERNAME=manmetr1_internet35"  
    echo "    DB_PASSWORD=<password_dari_cpanel>"
    echo ""
    echo "  Setelah edit .env, jalankan ulang script ini."
    echo ""
    exit 0
else
    echo "  .env exists, skipping..."
fi

# ---- STEP 5: Run migrations ----
echo ""
echo "[5/8] Running migrations..."
cd ${APP_DIR}
php artisan migrate --force

# Uncomment baris berikut untuk seed pertama kali:
# php artisan db:seed --force

# ---- STEP 6: Setup public directory ----
echo ""
echo "[6/8] Setting up document root..."

# Backup existing files (first time only)
if [ ! -f "${PUBLIC_DIR}/.laravel-deployed" ]; then
    echo "  Backing up existing files..."
    mkdir -p ${HOME_DIR}/wifi35.net.backup
    cp -r ${PUBLIC_DIR}/* ${HOME_DIR}/wifi35.net.backup/ 2>/dev/null || true
    cp ${PUBLIC_DIR}/.htaccess ${HOME_DIR}/wifi35.net.backup/ 2>/dev/null || true
fi

# Copy public files from Laravel
echo "  Copying public assets..."
cp -r ${APP_DIR}/public/build ${PUBLIC_DIR}/ 2>/dev/null || true
cp -r ${APP_DIR}/public/assets ${PUBLIC_DIR}/ 2>/dev/null || true
cp ${APP_DIR}/public/favicon.ico ${PUBLIC_DIR}/ 2>/dev/null || true
cp ${APP_DIR}/public/robots.txt ${PUBLIC_DIR}/ 2>/dev/null || true

# Copy .htaccess from Laravel
cp ${APP_DIR}/public/.htaccess ${PUBLIC_DIR}/

# Create custom index.php that points to app above webroot
echo "  Creating custom index.php..."
cat > ${PUBLIC_DIR}/index.php << 'PHPEOF'
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Path ke Laravel app (di atas document root)
$appPath = dirname(__DIR__) . '/internet35-app';

// Maintenance mode
if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader
require $appPath . '/vendor/autoload.php';

// Bootstrap & handle request
(require_once $appPath . '/bootstrap/app.php')
    ->handleRequest(Request::capture());
PHPEOF

# Storage symlink
echo "  Creating storage symlink..."
rm -f ${PUBLIC_DIR}/storage
ln -sf ${APP_DIR}/storage/app/public ${PUBLIC_DIR}/storage

# Mark as deployed
touch ${PUBLIC_DIR}/.laravel-deployed

# ---- STEP 7: Permissions ----
echo ""
echo "[7/8] Setting permissions..."
chmod -R 755 ${APP_DIR}
chmod -R 775 ${APP_DIR}/storage
chmod -R 775 ${APP_DIR}/bootstrap/cache

# ---- STEP 8: Optimize ----
echo ""
echo "[8/8] Optimizing..."
cd ${APP_DIR}
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link 2>/dev/null || true

echo ""
echo "============================================"
echo " ✓ Deployment complete!"
echo "============================================"
echo ""
echo " URL     : https://wifi35.net"
echo " App Dir : ${APP_DIR}"
echo " Public  : ${PUBLIC_DIR}"
echo ""
echo " ┌──────────────────────────────────────────┐"
echo " │  JANGAN LUPA setup cron di cPanel:       │"
echo " │  Cron Jobs → Add New Cron Job:           │"
echo " │  Every Minute (* * * * *)                │"
echo " │  Command:                                │"
echo " │  cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
echo " └──────────────────────────────────────────┘"
echo ""
