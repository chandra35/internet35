#!/bin/bash
for u in admin root genieacs ubuntu; do
  for p in admin kosongkosong kosong genieacs; do
    r=$(sshpass -p "$p" ssh -o StrictHostKeyChecking=no -o ConnectTimeout=3 -o PreferredAuthentications=password "$u@172.10.10.254" 'whoami' 2>&1)
    echo "$u/$p => $r"
  done
done
