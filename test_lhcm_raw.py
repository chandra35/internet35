#!/usr/bin/env python3
import urllib.request, urllib.parse, json, sys

nbi = 'http://172.10.10.254:7557'
device_id = '00259E-HG8245H-485754436ED42F9A'

q = json.dumps({'_id': device_id})
url = nbi + '/devices?query=' + urllib.parse.quote(q)
data = json.loads(urllib.request.urlopen(url, timeout=10).read())

dev = data[0]
igd = dev.get('InternetGatewayDevice', {})
lhcm = igd['LANDevice']['1']['LANHostConfigManagement']

# Print raw structure of key fields
fields = ['IPRouters', 'SubnetMask', 'DHCPServerEnable', 'DHCPLeaseTime', 'MinAddress', 'MaxAddress', 'DNSServers']
for f in fields:
    val = lhcm.get(f, 'KEY_MISSING')
    if val == 'KEY_MISSING':
        print(f + ': KEY MISSING')
    else:
        print(f + ':', json.dumps(val))

# Also check IPInterface sub-object (Huawei might store IP here)
ipif = lhcm.get('IPInterface', {})
print('\nIPInterface keys:', list(ipif.keys()) if isinstance(ipif, dict) else 'not a dict')
for k, v in ipif.items() if isinstance(ipif, dict) else []:
    if not k.startswith('_') and isinstance(v, dict):
        print('  IPInterface[' + k + ']:', {ik: iv for ik,iv in v.items() if not ik.startswith('_')})
