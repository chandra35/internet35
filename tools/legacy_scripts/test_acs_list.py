#!/usr/bin/env python3
import urllib.request, urllib.parse, json, sys

nbi = 'http://172.10.10.254:7557'

# Get all devices with minimal projection
url = nbi + '/devices?limit=20&projection=_id'
data = json.loads(urllib.request.urlopen(url, timeout=10).read())
print('Total devices:', len(data))
for d in data:
    print(' ', d['_id'])
