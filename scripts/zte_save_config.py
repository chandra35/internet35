#!/usr/bin/env python3
"""Save ZTE C320 running config via telnet and verify/add trap host."""
import telnetlib, time, sys, re

HOST = '136.1.1.100'
PORT = 23
USER = b'zte'
PASS = b'zte'
OUR_IP = '172.10.10.253'

# Trap add command (ZTE C320 syntax from configure terminal mode)
ADD_TRAP_CMD = (
    f'snmp-server host {OUR_IP} version 2c combro enable NOTIFICATIONS '
    f'target-addr-name internet35 isnmsserver udp-port 162 trap-report-compatibility v20'
).encode()

TIMEOUT = 5

def read_until_prompt(tn, timeout=TIMEOUT):
    """Read until ZXAN# or ZXAN(...)# prompt."""
    return tn.read_until(b'#', timeout).decode(errors='replace')

def cmd(tn, command, wait=TIMEOUT):
    """Send command + CRLF and read until prompt."""
    if isinstance(command, str):
        command = command.encode()
    tn.write(command + b'\r\n')
    return read_until_prompt(tn, wait)

try:
    tn = telnetlib.Telnet(HOST, PORT, timeout=15)

    # Login
    r = tn.read_until(b'Username:', TIMEOUT).decode(errors='replace')
    print('[login]', r.strip()[-60:])
    tn.write(USER + b'\r\n')

    r = tn.read_until(b'Password:', TIMEOUT).decode(errors='replace')
    print('[user]', r.strip()[-40:])
    tn.write(PASS + b'\r\n')

    r = read_until_prompt(tn, TIMEOUT)
    print('[pass]', r.strip()[-60:])

    # Disable pager
    cmd(tn, 'terminal length 0')

    # Check trap host
    r = cmd(tn, 'show snmp configuration', 6)
    if OUR_IP in r:
        idx = r.find(OUR_IP)
        print(f'[TRAP HOST OK] found: {r[max(0,idx-30):idx+100].strip()}')
        need_add = False
    else:
        print(f'[TRAP HOST MISSING] {OUR_IP} — will add via configure terminal')
        need_add = True

    if need_add:
        # Enter configure terminal
        r = cmd(tn, 'configure terminal', 3)
        print('[conf t]', r.strip()[-60:])

        # Add trap host
        r = cmd(tn, ADD_TRAP_CMD.decode(), 4)
        print('[add trap]', r.strip()[-200:])

        # Exit configure mode
        r = cmd(tn, 'exit', 3)
        print('[exit conf]', r.strip()[-60:])

        # Verify
        r = cmd(tn, 'show snmp configuration', 6)
        if OUR_IP in r:
            idx = r.find(OUR_IP)
            print(f'[TRAP HOST ADDED OK] {r[max(0,idx-30):idx+100].strip()}')
        else:
            print('[TRAP HOST ADD FAILED] still not found in show snmp configuration')

    # Save config
    r = cmd(tn, 'write', 6)
    print('[write]', r.strip()[-200:])
    if '%Error' in r or 'invalid' in r.lower():
        print('[write failed, trying write file]')
        r = cmd(tn, 'write file', 6)
        print('[write file]', r.strip()[-200:])

    tn.close()
    print('Done.')

except Exception as e:
    print(f'Error: {e}')
    sys.exit(1)


