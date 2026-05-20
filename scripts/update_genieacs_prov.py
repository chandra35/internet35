#!/usr/bin/env python3
"""Add ConnectionRequestURL declare to GenieACS default provision."""
import json
import urllib.request

NBI = 'http://172.10.10.254:7557'

with urllib.request.urlopen(f'{NBI}/provisions') as r:
    provs = json.load(r)

default_prov = next(p for p in provs if p['_id'] == 'default')
script = default_prov['script']

if 'ConnectionRequestURL' in script:
    print('Already present, skipping.')
else:
    inject = (
        '\n// Fetch ConnectionRequestURL so GenieACS can wake device for tasks\n'
        'declare("InternetGatewayDevice.ManagementServer.ConnectionRequestURL", {value: daily});\n'
    )
    marker = '//---------------------------- Remot Wan'
    if marker in script:
        script = script.replace(marker, inject + marker, 1)
    else:
        script = script + inject

    payload = json.dumps({'script': script}).encode('utf-8')
    req = urllib.request.Request(
        f'{NBI}/provisions/default',
        data=payload,
        method='PUT',
        headers={'Content-Type': 'application/json'},
    )
    try:
        with urllib.request.urlopen(req) as r:
            print('Updated. HTTP', r.status)
            print(r.read().decode())
    except urllib.error.HTTPError as e:
        print('HTTP Error:', e.code, e.reason)
        print('Body:', e.read().decode())
