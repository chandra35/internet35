#!/bin/bash
DEV="00259E-EG8141H5-48575443D38C26AA"
echo "=== Trigger CR (refreshObject ManagementServer) ==="
curl -s -X POST "http://172.10.10.254:7557/devices/${DEV}/tasks?connection_request" \
  -H 'Content-Type: application/json' \
  -d '{"name":"refreshObject","objectName":"InternetGatewayDevice.ManagementServer"}' \
  -w '\nHTTP=%{http_code}\n'
echo
echo "=== Faults ==="
curl -s "http://172.10.10.254:7557/faults/?query=$(php -r 'echo urlencode(json_encode(["device"=>"00259E-EG8141H5-48575443D38C26AA"]));')"
