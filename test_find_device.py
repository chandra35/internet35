#!/usr/bin/env python3
import urllib.request, urllib.parse, json, sys

nbi = 'http://172.10.10.254:7557'

# Search by partial serial hex
hex_partial = '6ED42F9A'
q = json.dumps({'_id': {'$regex': hex_partial, '$options': 'i'}})
url = nbi + '/devices?query=' + urllib.parse.quote(q)
data = json.loads(urllib.request.urlopen(url, timeout=10).read())
print('Found by ID regex:', len(data))
for d in data:
    print('  ID:', d['_id'])

# Also try: get all devices and look for HG8245H
url2 = nbi + '/devices?limit=100&projection=_id'
all_devs = json.loads(urllib.request.urlopen(url2, timeout=10).read())
print('Total devices (limit 100):', len(all_devs))
hg = [d['_id'] for d in all_devs if 'HG8245H' in d['_id']]
print('HG8245H devices:', len(hg))
for h in hg:
    print(' ', h)
