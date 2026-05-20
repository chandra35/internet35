#!/bin/bash
NBI="http://172.10.10.254:7557"

for SN in "HWTCD38C26AA" "HWTC6ED42F9A"; do
    echo "==============================="
    echo "ONU: $SN"
    echo "==============================="

    # Cari device ID
    DEVID=$(curl -s "$NBI/devices?query=%7B%22DeviceID.SerialNumber%22%3A%22${SN}%22%7D&projection=_id" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d[0]['_id'] if d else 'NOT_FOUND')" 2>/dev/null)
    echo "Device ID: $DEVID"

    if [ "$DEVID" = "NOT_FOUND" ] || [ -z "$DEVID" ]; then
        echo "Device tidak ditemukan di GenieACS"
        continue
    fi

    # Cek ManagementServer
    echo "--- ManagementServer ---"
    curl -s "$NBI/devices?query=%7B%22_id%22%3A%22${DEVID}%22%7D&projection=InternetGatewayDevice.ManagementServer" | python3 -c "
import sys, json
d = json.load(sys.stdin)
if not d: print('empty'); sys.exit()
ms = d[0].get('InternetGatewayDevice',{}).get('ManagementServer',{})
for k,v in ms.items():
    if not k.startswith('_'):
        val = v.get('_value','') if isinstance(v,dict) else v
        print(f'  {k}: {val}')
" 2>/dev/null

    # Cek WAN
    echo "--- WAN Device ---"
    curl -s "$NBI/devices?query=%7B%22_id%22%3A%22${DEVID}%22%7D&projection=InternetGatewayDevice.WANDevice.1.WANConnectionDevice" | python3 -c "
import sys, json
d = json.load(sys.stdin)
if not d: print('empty'); sys.exit()
wcd = d[0].get('InternetGatewayDevice',{}).get('WANDevice',{}).get('1',{}).get('WANConnectionDevice',{})
for widx, wval in wcd.items():
    if widx.startswith('_'): continue
    print(f'WCD.{widx}:')
    # WANIPConnection
    for conn_type in ['WANIPConnection','WANPPPConnection']:
        conns = wval.get(conn_type, {})
        for cidx, cval in conns.items():
            if cidx.startswith('_'): continue
            print(f'  {conn_type}.{cidx}:')
            for k,v in cval.items():
                if k.startswith('_'): continue
                val = v.get('_value','') if isinstance(v,dict) else ''
                if k in ['ConnectionType','ConnectionStatus','ExternalIPAddress','PPPoEUsername','PPPoEPassword','VLANID','Enable','Name']:
                    print(f'    {k}: {val}')
" 2>/dev/null

    echo ""
done
