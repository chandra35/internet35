#!/bin/bash
set -e
cd /www/wwwroot/internet35

# Replace the FIBERHOME_WEBUI_PASSWORD line with a single-quoted version.
PASS="%0|F?H@f!berhO3e"
# Remove existing lines (possibly multiple), then append the canonical ones
grep -v '^FIBERHOME_WEBUI_PASSWORD=' .env | grep -v '^FIBERHOME_WEBUI_USER=' > .env.tmp
mv .env.tmp .env
echo "FIBERHOME_WEBUI_USER=admin" >> .env
echo "FIBERHOME_WEBUI_PASSWORD='${PASS}'" >> .env

echo "--- tail .env ---"
tail -3 .env

/www/server/php/83/bin/php artisan config:clear
/www/server/php/83/bin/php artisan cache:clear

echo
echo "--- verify via Laravel config() ---"
/www/server/php/83/bin/php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "user=" . config("services.fiberhome.webui_user") . PHP_EOL;
echo "pass=" . config("services.fiberhome.webui_password") . PHP_EOL;
echo "match=" . (config("services.fiberhome.webui_password") === "%0|F?H@f!berhO3e" ? "YES" : "NO") . PHP_EOL;
'
