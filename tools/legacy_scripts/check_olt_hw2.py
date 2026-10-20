import socket, time

def cmd(s, c, w=3):
    s.send((c + '\r\n').encode())
    time.sleep(w)
    d = b''
    s.setblocking(False)
    try:
        while True:
            ch = s.recv(4096)
            if not ch:
                break
            d += ch
    except:
        pass
    s.setblocking(True)
    return d.decode('ascii', errors='ignore')

s = socket.socket()
s.settimeout(10)
s.connect(('136.1.1.100', 23))
time.sleep(1)
s.recv(4096)
s.send(b'zte\r\n')
time.sleep(1)
s.recv(4096)
s.send(b'zte\r\n')
time.sleep(2)
s.recv(4096)
cmd(s, 'terminal length 0', 1)

# VLAN full list
print('=== SHOW VLAN ALL ===')
print(cmd(s, 'show vlan all', 5))

# Service ports (VLAN bindings)
print('=== SHOW SERVICE-PORT ALL ===')
r = cmd(s, 'show service-port all', 5)
# Just first 3000 chars
print(r[:3000])

# GPON interfaces
print('=== SHOW INTERFACE GPON-OLT ===')
print(cmd(s, 'show interface gpon-olt_1/1/1', 4))

# PON port status for card in slot 1 (GTGHK with 16 ports)
print('=== SHOW GPON ONU STATE ALL PORTS ===')
for port in range(1, 17):
    r = cmd(s, f'show gpon onu state gpon-olt_1/1/{port}', 2)
    # Just the ONU Number line
    for line in r.split('\n'):
        if 'ONU Number' in line:
            print(f'  Port 1/1/{port}: {line.strip()}')
            break
    else:
        if 'No related' in r or 'error' in r.lower():
            print(f'  Port 1/1/{port}: No ONUs')

s.close()
