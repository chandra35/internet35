#!/bin/bash
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 'systemctl is-active genieacs-ui && journalctl -u genieacs-ui -n 3 --no-pager 2>/dev/null | tail -3'
