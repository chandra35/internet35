#!/bin/bash
NBI="http://172.10.10.254:7557"

DEVICES=(
  "ONU16-HWTCD38C26AA|00259E-EG8141H5-48575443D38C26AA"
  "ONU19-HWTC6ED42F9A|00259E-HG8245H-485754436ED42F9A"
)

for ENTRY in "${DEVICES[@]}"; do
  LABEL="${ENTRY%%|*}"
  DEVID="${ENTRY##*|}"

  echo "==============================="
  echo "$LABEL  =>  $DEVID"
  echo "==============================="

  python3 -c "
import urllib.request, json, urllib.parse

nbi = '$NBI'
devid = '$DEVID'

query = json.dumps({'_id': devid})
proj = 'InternetGatewayDevice.ManagementServer,InternetGatewayDevice.WANDevice.1.WANConnectionDevice'
url = nbi + '/devices?query=' + urllib.parse.quote(query) + '&projection=' + urllib.parse.quote(proj)

with urllib.request.urlopen(url, timeout=15) as r:
    d = json.loads(r.read())

if not d:
    print('Device tidak ditemukan')
    exit()

dev = d[0]

# ManagementServer
ms = dev.get('InternetGatewayDevice',{}).get('ManagementServer',{})
print('--- ManagementServer ---')
for k in ['Username','Password','ConnectionRequestUsername','ConnectionRequestPassword','URL']:
    v = ms.get(k,{})
    val = v.get('_value','(not set)') if isinstance(v,dict) else '(not set)'
    print(f'  {k}: {val}')

# WAN
print('--- WAN Connections ---')
wcd = dev.get('InternetGatewayDevice',{}).get('WANDevice',{}).get('1',{}).get('WANConnectionDevice',{})
if not any(k for k in wcd if not k.startswith('_')):
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
" 2>&1
  echo ""
done
