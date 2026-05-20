#!/bin/bash
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 'systemctl restart genieacs-cwmp genieacs-nbi genieacs-ui'
