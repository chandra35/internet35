#!/bin/bash
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 'mongo genieacs --quiet --eval "db.config.find().forEach(function(c){print(c._id+\" = \"+c.value)})"'
echo "---STATUS---"
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 'systemctl is-active genieacs-cwmp genieacs-nbi genieacs-ui'
echo "---LAST UI LOG---"
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 'journalctl -u genieacs-ui --no-pager -n 4 2>/dev/null'
