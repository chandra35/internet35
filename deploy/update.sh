#!/bin/bash
# ============================================================
# Internet35 - Quick Update Script
#
# Default server target:
#   APP_DIR=/www/wwwroot/internet35
#   PUBLIC_DIR=/www/wwwroot/internet35/public
#
# Jalankan di server:
#   cd /www/wwwroot/internet35 && bash deploy/update.sh
#
# Override lokasi jika perlu:
#   APP_DIR=/path/app PUBLIC_DIR=/path/public bash deploy/update.sh
#
# Setelah restore database:
#   RESTORE_DB=1 bash deploy/update.sh
#
# Jika role/permission ikut kacau setelah restore database:
#   RESTORE_DB=1 RESEED_RBAC=1 bash deploy/update.sh
# ============================================================

set -euo pipefail

APP_DIR="${APP_DIR:-/www/wwwroot/internet35}"
PUBLIC_DIR="${PUBLIC_DIR:-${APP_DIR}/public}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
AUTH_REPAIR_ARGS=()

if [ "${RESTORE_DB:-0}" = "1" ]; then
    AUTH_REPAIR_ARGS+=(--flush-sessions)
fi

if [ "${RESEED_RBAC:-0}" = "1" ]; then
    AUTH_REPAIR_ARGS+=(--reseed-rbac)
fi

if [ ! -d "${APP_DIR}" ]; then
    echo "ERROR: APP_DIR tidak ditemukan: ${APP_DIR}"
    exit 1
fi

cd "${APP_DIR}"

if [ ! -f artisan ]; then
    echo "ERROR: artisan tidak ditemukan di ${APP_DIR}"
    exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet || [ -n "$(git ls-files --others --exclude-standard)" ]; then
    echo "ERROR: working tree git masih kotor. Rapikan dulu sebelum update."
    git status --short
    exit 1
fi

export COMPOSER_ALLOW_SUPERUSER=1

echo "==============================="
echo " Updating Internet35..."
echo " App    : ${APP_DIR}"
echo " Public : ${PUBLIC_DIR}"
echo " Branch : ${BRANCH}"
echo "==============================="

echo "[1/6] Git pull..."
git pull --ff-only origin "${BRANCH}"

echo "[2/6] Composer install..."
"${COMPOSER_BIN}" install --no-dev --optimize-autoloader --no-interaction 2>&1

echo "[3/6] Frontend assets..."
if command -v npm >/dev/null 2>&1 && [ -f package.json ]; then
    npm ci --no-fund --no-audit 2>&1
    npm run build 2>&1
elif [ -d public/build ]; then
    echo "    npm tidak tersedia, pakai asset build yang sudah ada."
else
    echo "    WARNING: public/build belum ada dan npm tidak tersedia."
fi

echo "[4/6] Migrate..."
"${PHP_BIN}" artisan migrate --force

echo "[5/6] Repair auth & cache..."
if [ "${RESTORE_DB:-0}" = "1" ]; then
    echo "    Restore DB mode aktif."
fi

if [ "${RESEED_RBAC:-0}" = "1" ]; then
    echo "    RBAC reseed aktif."
fi

"${PHP_BIN}" artisan app:repair-auth "${AUTH_REPAIR_ARGS[@]}"
"${PHP_BIN}" artisan config:clear
"${PHP_BIN}" artisan route:clear
"${PHP_BIN}" artisan view:clear
"${PHP_BIN}" artisan config:cache
"${PHP_BIN}" artisan route:cache
"${PHP_BIN}" artisan view:cache
"${PHP_BIN}" artisan storage:link 2>/dev/null || true

# Fix ownership for web server (artisan runs as root, creating root-owned files)
WEB_USER="${WEB_USER:-www}"
echo "    Fixing file ownership to ${WEB_USER}..."
chown -R "${WEB_USER}:${WEB_USER}" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo "[6/6] Public path check..."
if [ "${PUBLIC_DIR}" = "${APP_DIR}/public" ]; then
    echo "    Public root sudah langsung mengarah ke ${APP_DIR}/public."
elif [ -d "${PUBLIC_DIR}" ]; then
    echo "    PUBLIC_DIR custom terdeteksi: ${PUBLIC_DIR}"
    echo "    Sinkronisasi manual belum diaktifkan di mode ini."
else
    echo "    WARNING: PUBLIC_DIR tidak ditemukan: ${PUBLIC_DIR}"
fi

echo ""
echo "==============================="
echo " Update complete!"
echo "==============================="
