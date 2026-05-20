#!/bin/bash
PHP="/www/server/php/83/bin/php"
APP="/www/wwwroot/internet35"

# Cek ONU data di DB
echo "=== ONU DB DATA ==="
mysql -u internet35 -pbilling35db internet35 -e "
SELECT id, name, serial_number, onu_id, pppoe_username, config_status, status,
       vlan_config
FROM onus
WHERE serial_number = 'HWTC0EDD2AAF'
" 2>&1

echo ""
echo "=== JOBS QUEUE (pending) ==="
mysql -u internet35 -pbilling35db internet35 -e "
SELECT id, queue, LEFT(payload,200) as payload_preview, attempts, available_at, created_at
FROM jobs
ORDER BY created_at DESC
LIMIT 10
" 2>&1

echo ""
echo "=== FAILED JOBS ==="
mysql -u internet35 -pbilling35db internet35 -e "
SELECT id, queue, LEFT(payload,200) as payload_preview, LEFT(exception,300) as exception, failed_at
FROM failed_jobs
ORDER BY failed_at DESC
LIMIT 5
" 2>&1
