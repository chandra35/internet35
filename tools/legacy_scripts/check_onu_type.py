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

# Check ONU types for all ONUs on port 1/1/1
print('=== ONU AUTH INFO (types) ===')
print(cmd(s, 'show gpon onu baseinfo gpon-olt_1/1/1', 5))

# Check OMCI info for ONU 19 to see real model
print('=== ONU 19 OMCI VERSION ===')
print(cmd(s, 'show gpon remote-onu equip gpon-onu_1/1/1:19', 4))

# Compare with working ONU 1 
print('=== ONU 1 OMCI VERSION (working) ===')
print(cmd(s, 'show gpon remote-onu equip gpon-onu_1/1/1:1', 4))

# Check if config download is stuck
print('=== ONU 19 CONFIG DOWNLOAD ===')
print(cmd(s, 'show gpon onu config-download-status gpon-onu_1/1/1:19', 4))

s.close()
