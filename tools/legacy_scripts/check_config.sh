#!/bin/bash
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 '
echo "=== All config with @ ==="
mongo genieacs --quiet --eval "db.config.find().forEach(function(c){printjson({_id:c._id,value:c.value})})"
echo ""
echo "=== Service status ==="
systemctl is-active genieacs-cwmp genieacs-nbi genieacs-ui
echo ""
echo "=== UI latest error ==="
journalctl -u genieacs-ui -n 5 --no-pager 2>/dev/null | grep -i "error\|parsing\|active"
'
