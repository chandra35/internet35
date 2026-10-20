#!/bin/bash
NBI="http://172.10.10.254:7557"

for SN in "HWTCD38C26AA" "HWTC6ED42F9A"; do
    echo "==============================="
    echo "ONU SN: $SN"
    echo "==============================="

    # Cari device - gunakan python untuk URL encode
    RESULT=$(python3 -c "
import urllib.request, json, urllib.parse
query = json.dumps({'DeviceID.SerialNumber': '$SN'})
url = '${NBI}/devices?query=' + urllib.parse.quote(query)
try:
    with urllib.request.urlopen(url, timeout=10) as r:
        data = json.loads(r.read())
        if data:
            print(data[0].get('_id',''))
        else:
            print('NOT_FOUND')
except Exception as e:
    print('ERROR:' + str(e))
")
    DEVID="$RESULT"
    echo "Device ID: $DEVID"

    if [[ "$DEVID" == NOT_FOUND* ]] || [[ "$DEVID" == ERROR* ]] || [ -z "$DEVID" ]; then
        echo "Device tidak ditemukan"
        continue
    fi

    # Cek ManagementServer + WAN
    python3 -c "
import urllib.request, json, urllib.parse
nbi = '${NBI}'
devid = '$DEVID'

query = json.dumps({'_id': devid})
proj = 'InternetGatewayDevice.ManagementServer,InternetGatewayDevice.WANDevice.1.WANConnectionDevice'
url = nbi + '/devices?query=' + urllib.parse.quote(query) + '&projection=' + urllib.parse.quote(proj)

with urllib.request.urlopen(url, timeout=15) as r:
    d = json.loads(r.read())

if not d:
    print('No data')
    exit()

dev = d[0]

# ManagementServer
ms = dev.get('InternetGatewayDevice',{}).get('ManagementServer',{})
print('--- ManagementServer ---')
for k in ['Username','Password','ConnectionRequestUsername','ConnectionRequestPassword']:
    v = ms.get(k,{})
    val = v.get('_value','(not set)') if isinstance(v,dict) else '(not set)'
    print(f'  {k}: {val}')

# WAN
print('--- WAN Connections ---')
wcd = dev.get('InternetGatewayDevice',{}).get('WANDevice',{}).get('1',{}).get('WANConnectionDevice',{})
if not wcd:
    print('  (kosong - belum ada WCD)')
for widx, wval in wcd.items():
    if widx.startswith('_'): continue
    for conn_type in ['WANIPConnection','WANPPPConnection']:
        conns = wval.get(conn_type, {})
        for cidx, cval in conns.items():
            if cidx.startswith('_'): continue
            print(f'  WCD.{widx}.{conn_type}.{cidx}:')
            for k in ['Enable','ConnectionType','ConnectionStatus','ExternalIPAddress','PPPoEUsername','PPPoEPassword','X_HW_VLANID','VLANID','Name']:
                v = cval.get(k,{})
                val = v.get('_value','') if isinstance(v,dict) else ''
                if val != '':
                    print(f'    {k}: {val}')
"
    echo ""
done
