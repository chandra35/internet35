#!/bin/bash
echo "=== ping ONU ==="
ping -c 2 172.16.19.116 || true
echo
echo "=== VM 253 time ==="
date -u
echo
echo "=== VM 254 last log ==="
ssh -i ~/.ssh/id_ed25519_genieacs -o BatchMode=yes -o StrictHostKeyChecking=no root@172.10.10.254 bash /tmp/check_log.sh
