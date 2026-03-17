#!/bin/bash
# ============================================================
# Internet35 - Quick Redeploy (setelah update code)
# ============================================================

set -e
APP_DIR="/var/www/internet35"
cd ${APP_DIR}

echo "Redeploying Internet35..."

# Pull latest code (jika pakai git)
# git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

# Migrate
php artisan migrate --force

# Clear & rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
systemctl restart internet35-queue
systemctl reload php8.3-fpm

echo "Done! App updated."
