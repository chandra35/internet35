#!/bin/bash
curl -s 'http://172.10.10.254:7557/devices?projection=_id' | python3 -c "
import sys, json
d = json.load(sys.stdin)
for x in d:
    devid = x.get('_id','')
    if '0EDD2AAF' in devid:
        print(devid)
"
