#!/usr/bin/env python3
import urllib.request, urllib.parse, json, sys

serial = 'HWTC6ED42F9A'
nbi = 'http://172.10.10.254:7557'

def fetch(url):
    try:
        return json.loads(urllib.request.urlopen(url, timeout=10).read())
    except Exception as e:
        print('ERROR:', e); return []

# Try strategy 1: _deviceId._SerialNumber
q = json.dumps({'_deviceId._SerialNumber': {'$regex': serial, '$options': 'i'}})
data = fetch(nbi + '/devices?query=' + urllib.parse.quote(q))

# Strategy 2: DeviceInfo.SerialNumber
if not data:
    q = json.dumps({'InternetGatewayDevice.DeviceInfo.SerialNumber._value': {'$regex': serial, '$options': 'i'}})
    data = fetch(nbi + '/devices?query=' + urllib.parse.quote(q))

# Strategy 3: _id regex
if not data:
    q = json.dumps({'_id': {'$regex': serial, '$options': 'i'}})
    data = fetch(nbi + '/devices?query=' + urllib.parse.quote(q))

if not data:
    print('Device not found for serial:', serial)
    sys.exit(1)

dev = data[0]
print('=== Device ID:', dev['_id'])
igd = dev.get('InternetGatewayDevice', {})
landev = igd.get('LANDevice', {})
print('LANDevice top keys:', list(landev.keys()))

for ldKey, ldVal in landev.items():
    if not isinstance(ldVal, dict) or ldKey.startswith('_'):
        continue
    print('\n--- LANDevice.' + ldKey + ' sub-keys:', list(ldVal.keys()))

    # LANIPInterface
    liface = ldVal.get('LANIPInterface', {})
    if liface:
        print('  LANIPInterface keys:', list(liface.keys()))
        for k, v in liface.items():
            if not isinstance(v, dict) or k.startswith('_'):
                continue
            ip   = v.get('IPInterfaceIPAddress', {}).get('_value', 'NOT_FOUND')
            mask = v.get('SubnetMask', {}).get('_value', 'NOT_FOUND')
            print('    [' + str(k) + '] IPInterfaceIPAddress:', ip)
            print('    [' + str(k) + '] SubnetMask:', mask)
    else:
        print('  LANIPInterface: NOT PRESENT')

    # LANHostConfigManagement
    lhcm = ldVal.get('LANHostConfigManagement', {})
    if lhcm:
        print('  LHCM.IPRouters:', lhcm.get('IPRouters', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.SubnetMask:', lhcm.get('SubnetMask', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.DHCPServerEnable:', lhcm.get('DHCPServerEnable', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.MinAddress:', lhcm.get('MinAddress', {}).get('_value', 'NOT_FOUND'))
        print('  LHCM.MaxAddress:', lhcm.get('MaxAddress', {}).get('_value', 'NOT_FOUND'))
    else:
        print('  LANHostConfigManagement: NOT PRESENT')
