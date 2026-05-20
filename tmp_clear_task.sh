#!/bin/bash
NBI="http://172.10.10.254:7557"

# Hapus stale task getParameterValues di ONU 16
DEVID="00259E-EG8141H5-48575443D38C26AA"
TASK_ID="69e95e3e1b278e90c2419a97"

echo "Deleting stale task $TASK_ID on $DEVID..."
curl -s -X DELETE "$NBI/tasks/$TASK_ID" -w "\nHTTP %{http_code}\n"
echo ""

# Pastikan tidak ada task pending tersisa
echo "Remaining tasks ONU16:"
python3 -c "
import urllib.request, json, urllib.parse
query = json.dumps({'device': '$DEVID'})
url = '${NBI}/tasks?query=' + urllib.parse.quote(query)
with urllib.request.urlopen(url, timeout=10) as r:
    tasks = json.loads(r.read())
if not tasks:
    print('  (bersih - tidak ada task pending)')
for t in tasks:
    print(f'  {t.get(\"name\")} - {t.get(\"_id\")}')
"
