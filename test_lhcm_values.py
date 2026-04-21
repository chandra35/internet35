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

fields = ['IPRouters', 'SubnetMask', 'DHCPServerEnable', 'DHCPLeaseTime', 'MinAddress', 'MaxAddress', 'DNSServers', 'DomainName', 'MACAddress']
print('=== LANHostConfigManagement values ===')
for f in fields:
    val = lhcm.get(f, 'KEY_MISSING')
    if val == 'KEY_MISSING':
        print(f'  {f}: KEY_MISSING')
    elif isinstance(val, dict):
        v = val.get('_value', 'NO_VALUE')
        ts = val.get('_timestamp', 'no_ts')[:19] if val.get('_timestamp') else 'no_ts'
        print(f'  {f}: _value={v!r}  ts={ts}')
    else:
        print(f'  {f}: {val!r}')
