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

# Enter config and pon-onu-mng to see what's inside
print('=== ENTER CONFIG ===')
print(cmd(s, 'configure terminal', 2))

print('=== ENTER PON-ONU-MNG 19 ===')
print(cmd(s, 'pon-onu-mng gpon-onu_1/1/1:19', 2))

print('=== SHOW RUNNING-CONFIG INSIDE pon-onu-mng 19 ===')
r = cmd(s, 'show running-config', 5)
# Find our ONU 19 section
lines = r.split('\n')
in_section = False
for line in lines:
    if 'pon-onu-mng gpon-onu_1/1/1:19' in line:
        in_section = True
    if in_section:
        print(line)
        if line.strip() == '!' and in_section:
            break

print('\n=== EXIT AND CHECK DETAIL ===')
print(cmd(s, 'exit', 1))
print(cmd(s, 'exit', 1))

# Check detail-info for config state
print('=== ONU 19 DETAIL (Config) ===')
r = cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:19', 4)
for line in r.split('\n'):
    if 'Config' in line or 'Phase' in line or 'State' in line or 'Profile' in line:
        print(line.strip())

print('\n=== ONU 1 DETAIL (Config - working ONU) ===')
r = cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:1', 4)
for line in r.split('\n'):
    if 'Config' in line or 'Phase' in line or 'State' in line or 'Profile' in line:
        print(line.strip())

s.close()
