#!/bin/bash
echo "VM254 UTC: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "--- last 5 log entries ---"
tail -5 /var/log/genieacs/genieacs-cwmp-access.log
echo "--- last D38C26AA entry ---"
grep D38C26AA /var/log/genieacs/genieacs-cwmp-access.log | tail -5
