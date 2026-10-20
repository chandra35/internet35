#!/usr/bin/env python3
import urllib.request, urllib.parse, json, sys

nbi = 'http://172.10.10.254:7557'
device_id = '00259E-HG8245H-485754436ED42F9A'

q = json.dumps({'_id': device_id})
url = nbi + '/devices?query=' + urllib.parse.quote(q)
data = json.loads(urllib.request.urlopen(url, timeout=10).read())

if not data:
    print('Device not found'); sys.exit(1)

dev = data[0]
print('=== Device ID:', dev['_id'])
igd = dev.get('InternetGatewayDevice', {})
landev = igd.get('LANDevice', {})

if not landev:
    print('LANDevice: NOT PRESENT in response')
    print('Top-level IGD keys:', list(igd.keys()))
    sys.exit(0)

print('LANDevice top keys:', list(landev.keys()))

for ldKey, ldVal in landev.items():
    if not isinstance(ldVal, dict) or ldKey.startswith('_'):
        continue
    print('\n--- LANDevice.' + ldKey + ':')
    print('  Sub-keys:', list(ldVal.keys()))

    # Check LANIPInterface
    liface = ldVal.get('LANIPInterface', {})
    if liface:
        print('  LANIPInterface present, keys:', list(liface.keys()))
        for k, v in liface.items():
            if not isinstance(v, dict) or k.startswith('_'):
                continue
            ip   = v.get('IPInterfaceIPAddress', {}).get('_value', 'NOT_FOUND')
            mask = v.get('SubnetMask', {}).get('_value', 'NOT_FOUND')
            gw   = v.get('DefaultGateway', {}).get('_value', 'N/A')
            print('    [' + str(k) + '] IP:', ip, '| Mask:', mask, '| GW:', gw)
            print('      All sub-keys:', list(v.keys()))
    else:
        print('  LANIPInterface: NOT PRESENT')

    # Check LANHostConfigManagement
    lhcm = ldVal.get('LANHostConfigManagement', {})
    if lhcm:
        print('  LANHostConfigManagement keys (non-_):', [k for k in lhcm.keys() if not k.startswith('_')])
        print('  LHCM.IPRouters:', lhcm.get('IPRouters', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.SubnetMask:', lhcm.get('SubnetMask', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.DHCPServerEnable:', lhcm.get('DHCPServerEnable', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.MinAddress:', lhcm.get('MinAddress', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.MaxAddress:', lhcm.get('MaxAddress', {}).get('_value', 'NOT_FOUND'))
    else:
        print('  LANHostConfigManagement: NOT PRESENT')
