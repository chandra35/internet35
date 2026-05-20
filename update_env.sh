#!/bin/bash
sed -i "s|^GENIEACS_CWMP_USERNAME=.*|GENIEACS_CWMP_USERNAME=acs|" /www/wwwroot/internet35/.env
sed -i "s|^GENIEACS_CWMP_PASSWORD=.*|GENIEACS_CWMP_PASSWORD=acs@internet35|" /www/wwwroot/internet35/.env
echo "=== Result ==="
grep GENIEACS_CWMP /www/wwwroot/internet35/.env
