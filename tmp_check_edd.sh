#!/bin/bash
NBI="http://172.10.10.254:7557"
DEVID="00259E-HG8145X6%2D10-485754430EDD2AAF"

echo "=== PENDING TASKS ==="
python3 -c "
import urllib.request, json, urllib.parse
devid = '$DEVID'
query = json.dumps({'device': devid})
url = '$NBI/tasks?query=' + urllib.parse.quote(query)
with urllib.request.urlopen(url, timeout=10) as r:
    tasks = json.loads(r.read())
if not tasks:
    print('  (tidak ada task pending)')
for t in tasks:
    print(f'  [{t.get(\"_id\")}] {t.get(\"name\")} created={t.get(\"timestamp\",\"?\")}')
    if t.get('name') == 'setParameterValues':
        for p in t.get('parameterValues', [])[:10]:
            print(f'    {p[0]} = {p[1]}')
    elif t.get('name') == 'addObject':
        print(f'    objectName: {t.get(\"objectName\")}')
"

echo ""
echo "=== WAN STATE ==="
python3 -c "
import urllib.request, json, urllib.parse
devid = '$DEVID'
query = json.dumps({'_id': devid})
proj = 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice,InternetGatewayDevice.ManagementServer'
url = '$NBI/devices?query=' + urllib.parse.quote(query) + '&projection=' + urllib.parse.quote(proj)
with urllib.request.urlopen(url, timeout=15) as r:
    d = json.loads(r.read())
if not d:
    print('Device tidak ditemukan')
    exit()
dev = d[0]

# ManagementServer
ms = dev.get('InternetGatewayDevice',{}).get('ManagementServer',{})
print('ManagementServer:')
for k in ['Username','Password','ConnectionRequestUsername','ConnectionRequestPassword']:
    v = ms.get(k,{})
    val = v.get('_value','(not set)') if isinstance(v,dict) else '(not set)'
    print(f'  {k}: {val}')

# WAN
wcd = dev.get('InternetGatewayDevice',{}).get('WANDevice',{}).get('1',{}).get('WANConnectionDevice',{})
print('WANConnectionDevice:')
if not any(k for k in wcd if not k.startswith('_')):
    print('  (kosong)')
for widx, wval in wcd.items():
    if widx.startswith('_'): continue
    for ct in ['WANIPConnection','WANPPPConnection']:
        conns = wval.get(ct,{})
        for cidx, cval in conns.items():
            if cidx.startswith('_'): continue
            print(f'  WCD.{widx}.{ct}.{cidx}:')
            for k in ['Enable','ConnectionStatus','ExternalIPAddress','PPPoEUsername','X_HW_VLANID','VLANID','Name','X_HW_SERVICELIST']:
                v = cval.get(k,{})
                val = v.get('_value','') if isinstance(v,dict) else ''
                if val != '': print(f'    {k}: {val}')
"
