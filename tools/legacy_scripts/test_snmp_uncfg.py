#!/usr/bin/env python3
"""Test SNMP walk on ZTE uncfg ONU table columns to see what data exists."""
import subprocess, sys

OLT_IP = '136.1.1.100'
COMMUNITY = 'public'  # adjust if different

# ZTE uncfg ONU table OIDs
oids = {
    'col1_unknown':  '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.1',
    'col2_serial':   '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.2',
    'col3_model':    '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.3',
    'col4_unknown':  '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.4',
    'col5_unknown':  '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.5',
    'col6_unknown':  '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.6',
}

for name, oid in oids.items():
    print(f'\n=== {name} ({oid}) ===')
    try:
        result = subprocess.run(
            ['snmpwalk', '-v2c', '-c', COMMUNITY, '-Oqn', '-t', '5', OLT_IP, oid],
            capture_output=True, text=True, timeout=10
        )
        out = result.stdout.strip()
        err = result.stderr.strip()
        if out:
            for line in out.split('\n')[:5]:  # show max 5 entries
                print(f'  {line}')
        elif err:
            print(f'  ERROR: {err}')
        else:
            print('  (empty / no data)')
    except Exception as e:
        print(f'  EXCEPTION: {e}')
