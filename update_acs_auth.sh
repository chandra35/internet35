#!/bin/bash
# Update GenieACS cwmp.connectionRequestAuth via NBI port 7557 PUT.
# Body harus expression literal (text/plain), bukan JSON.
EXPR='DeviceID.Manufacturer = "Huawei Technologies Co., Ltd" ? AUTH("acs", "acs@internet35") : AUTH("kosong", "kosong")'

echo "=== BEFORE ==="
curl -s 'http://172.10.10.254:7557/config' | python3 -c 'import sys,json;[print(c) for c in json.load(sys.stdin) if "connectionRequestAuth" in c["_id"]]'

echo ""
echo "=== PUT (HTTP 404 normal di GenieACS 1.2.x tapi value tetap ter-update) ==="
curl -s -X PUT 'http://172.10.10.254:7557/config/cwmp.connectionRequestAuth' \
  -H 'Content-Type: text/plain' \
  --data-binary "$EXPR" \
  -w 'HTTP=%{http_code}\n'

sleep 2

echo ""
echo "=== AFTER ==="
curl -s 'http://172.10.10.254:7557/config' | python3 -c 'import sys,json;[print(c) for c in json.load(sys.stdin) if "connectionRequestAuth" in c["_id"]]'
