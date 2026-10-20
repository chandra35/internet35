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

# Check config state now (after time has passed)
r = cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:19', 4)
for line in r.split('\n'):
    line = line.strip()
    if any(k in line for k in ['Config', 'Phase', 'State:', 'Online', 'Type:', 'Line Profile', 'Service Profile']):
        print(line)

# Check a working HWTC ONU (e.g., ONU 4 which is also HG8245H type)
print('\n--- Working ONU 4 (HG8245H) ---')
r = cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:4', 4)
for line in r.split('\n'):
    line = line.strip()
    if any(k in line for k in ['Config', 'Phase', 'State:', 'Online', 'Type:', 'Line Profile', 'Service Profile']):
        print(line)

# Check working ONU 2 (type ALL, HWTC)
print('\n--- Working ONU 2 (ALL type, HWTC) ---')
r = cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:2', 4)
for line in r.split('\n'):
    line = line.strip()
    if any(k in line for k in ['Config', 'Phase', 'State:', 'Online', 'Type:', 'Line Profile', 'Service Profile']):
        print(line)

# Try to manually trigger config download
print('\n--- TRY CONFIG DOWNLOAD ---')
print(cmd(s, 'configure terminal', 2))
print(cmd(s, 'interface gpon-onu_1/1/1:19', 2))
print(cmd(s, 'shutdown', 2))
print(cmd(s, 'no shutdown', 2))
print(cmd(s, 'exit', 1))
print(cmd(s, 'exit', 1))

time.sleep(5)

# Check config state after reboot
print('\n--- AFTER NO-SHUT ---')
r = cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:19', 4)
for line in r.split('\n'):
    line = line.strip()
    if any(k in line for k in ['Config', 'Phase', 'State:', 'Online']):
        print(line)

s.close()
