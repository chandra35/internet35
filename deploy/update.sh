#!/bin/bash
# ============================================================
# Internet35 - Quick Update Script
# 
# Jalankan di server:
#   cd /home/manmetr1/internet35-app && bash deploy/update.sh
# ============================================================

set -e
cd /home/manmetr1/internet35-app

echo "==============================="
echo " Updating Internet35..."
echo "==============================="

# Pull latest
echo "[1/5] Git pull..."
git pull origin main

# Composer install (jika ada package baru)
echo "[2/5] Composer install..."
composer install --no-dev --optimize-autoloader --no-interaction 2>&1

# Migrate (jika ada migration baru)
echo "[3/5] Migrate..."
php artisan migrate --force

# Copy public assets
echo "[4/5] Sync public assets..."
cp -r public/build /home/manmetr1/wifi35.net/ 2>/dev/null || true
cp -r public/assets /home/manmetr1/wifi35.net/ 2>/dev/null || true
cp public/.htaccess /home/manmetr1/wifi35.net/
cp public/favicon.ico /home/manmetr1/wifi35.net/ 2>/dev/null || true
cp public/robots.txt /home/manmetr1/wifi35.net/ 2>/dev/null || true

# Clear & re-cache
echo "[5/5] Clear cache & optimize..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "==============================="
echo " ✓ Update complete!"
echo "==============================="
