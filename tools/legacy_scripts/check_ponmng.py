import socket, time

def cmd(s, c, w=2):
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

# Check available pon-onu-mng commands
print('=== TRY show pon-onu-mng ===')
print(cmd(s, 'show pon-onu-mng gpon-onu_1/1/1:19', 3))

print('=== TRY show run pon-onu-mng ===')
print(cmd(s, 'show running-config pon-onu-mng gpon-onu_1/1/1:19', 3))

# Enter config mode and check pon-onu-mng context
print('=== ENTER CONFIG ===')
print(cmd(s, 'configure terminal', 2))

print('=== ENTER PON-ONU-MNG ===')
r = cmd(s, 'pon-onu-mng gpon-onu_1/1/1:19', 2)
print(r)

# Show available commands
print('=== SHOW COMMANDS IN PON-ONU-MNG ===')
print(cmd(s, '?', 3))

# Check current config inside pon-onu-mng
print('=== SHOW RUNNING INSIDE ===')
print(cmd(s, 'show running-config', 3))

# Try the service command to see what happens
print('=== TRY service internet ===')
print(cmd(s, 'service internet gemport 1 vlan 335', 3))

print('=== TRY vlan port ===')
print(cmd(s, 'vlan port eth_0/1 mode tag vlan 335', 3))

print('=== TRY mvlan ===')
print(cmd(s, 'mvlan 111', 3))

print('=== TRY wan-ip ===')
print(cmd(s, 'wan-ip 1 mode dhcp vlan-profile 111 host 1', 3))

print('=== EXIT ===')
print(cmd(s, 'exit', 1))
print(cmd(s, 'exit', 1))

s.close()
