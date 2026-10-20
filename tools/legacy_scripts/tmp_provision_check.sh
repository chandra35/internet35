#!/bin/bash
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 \
  'mongo genieacs --quiet --eval "printjson(db.provisions.findOne({_id: \"inform\"}))"' 2>&1
