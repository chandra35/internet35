#!/bin/bash
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 '
echo "=== Service status ==="
systemctl is-active genieacs-cwmp genieacs-nbi genieacs-ui
echo ""
echo "=== UI log tail ==="
journalctl -u genieacs-ui -n 20 --no-pager 2>/dev/null || tail -20 /var/log/genieacs/genieacs-ui.log 2>/dev/null
echo ""
echo "=== NBI log tail ==="
journalctl -u genieacs-nbi -n 10 --no-pager 2>/dev/null
'
