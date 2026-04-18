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

# 1. Physical cards/slots
print('=== CARD INFO ===')
print(cmd(s, 'show card', 5))

# 2. VLAN list
print('=== VLAN LIST ===')
print(cmd(s, 'show vlan', 5))

# 3. PON ports summary
print('=== INTERFACE BRIEF ===')
print(cmd(s, 'show interface brief gpon', 5))

# 4. Rack info  
print('=== SHELF/RACK ===')
print(cmd(s, 'show shelf', 3))

s.close()
