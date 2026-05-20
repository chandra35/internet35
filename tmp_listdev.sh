#!/bin/bash
curl -s 'http://172.10.10.254:7557/devices?projection=_id,DeviceID' | python3 -c "
import sys, json
d = json.load(sys.stdin)
for x in d:
    devid = x.get('_id','')
    sn = x.get('DeviceID',{}).get('SerialNumber',{}).get('_value','')
    mfr = x.get('DeviceID',{}).get('Manufacturer',{}).get('_value','')
    print(f'{devid} | {sn} | {mfr}')
"
