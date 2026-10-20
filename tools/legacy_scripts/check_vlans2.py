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

print('=== VLAN SUMMARY ===')
print(cmd(s, 'show vlan summary', 4))

print('=== VLAN 111 ===')
print(cmd(s, 'show vlan 111', 4))

print('=== VLAN 335 ===')
print(cmd(s, 'show vlan 335', 4))

print('=== VLAN DATABASE (running-config) ===')
print(cmd(s, 'show running-config | begin vlan database', 5))

s.close()
