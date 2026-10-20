#!/bin/bash
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 'journalctl -u genieacs-ui --no-pager -n 6 2>/dev/null'
