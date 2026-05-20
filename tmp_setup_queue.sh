#!/bin/bash
# Setup queue worker sebagai systemd service untuk internet35

SERVICE_FILE="/etc/systemd/system/internet35-queue.service"
PHP="/www/server/php/83/bin/php"
APP_DIR="/www/wwwroot/internet35"

cat > "$SERVICE_FILE" << 'EOF'
[Unit]
Description=Internet35 Laravel Queue Worker
After=network.target

[Service]
User=www
Group=www
WorkingDirectory=/www/wwwroot/internet35
ExecStart=/www/server/php/83/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

# Cek user www ada atau tidak
if ! id www &>/dev/null; then
    # Ganti ke www-data atau root
    if id www-data &>/dev/null; then
        sed -i 's/User=www/User=www-data/g; s/Group=www/Group=www-data/g' "$SERVICE_FILE"
    else
        sed -i 's/User=www/User=root/g; s/Group=www/Group=root/g' "$SERVICE_FILE"
    fi
fi

systemctl daemon-reload
systemctl enable internet35-queue
systemctl start internet35-queue
sleep 2
systemctl status internet35-queue --no-pager -l
