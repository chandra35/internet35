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

# Try different VLAN commands
print('=== show vlan ===')
print(cmd(s, 'show vlan', 3))

print('=== show vlan ? ===')
print(cmd(s, 'show vlan ?', 3))

print('=== show running vlan ===')
r = cmd(s, 'show running-config | include vlan', 8)
# Filter only vlan-relevant lines
lines = [l.strip() for l in r.split('\n') if 'vlan' in l.lower() and l.strip()]
for l in sorted(set(lines)):
    print(l)

print('\n=== show running-config vlan section ===')
r = cmd(s, 'show running-config | begin ^vlan', 5)
# Print first 80 lines
for i, line in enumerate(r.split('\n')[:80]):
    print(line)

s.close()
