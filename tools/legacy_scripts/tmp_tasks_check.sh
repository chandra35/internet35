#!/bin/bash
NBI="http://172.10.10.254:7557"

for DEVID in "00259E-EG8141H5-48575443D38C26AA" "00259E-HG8245H-485754436ED42F9A"; do
    echo "==============================="
    echo "Tasks for: $DEVID"
    echo "==============================="
    python3 -c "
import urllib.request, json, urllib.parse
nbi = '$NBI'
devid = '$DEVID'
query = json.dumps({'device': devid})
url = nbi + '/tasks?query=' + urllib.parse.quote(query)
with urllib.request.urlopen(url, timeout=10) as r:
    tasks = json.loads(r.read())
if not tasks:
    print('  (tidak ada task)')
for t in tasks:
    print(f'  [{t.get(\"_id\",\"?\")}] {t.get(\"name\",\"?\")} - created: {t.get(\"timestamp\",\"?\")}')
    if t.get('name') == 'setParameterValues':
        params = t.get('parameterValues', [])
        for p in params[:5]:
            print(f'    {p[0]} = {p[1]}')
        if len(params) > 5:
            print(f'    ... ({len(params)-5} more)')
    elif t.get('name') == 'addObject':
        print(f'    objectName: {t.get(\"objectName\",\"?\")}')
" 2>&1
    echo ""
done
