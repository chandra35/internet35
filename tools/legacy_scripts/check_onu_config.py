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

print('=== INTERFACE CONFIG ===')
print(cmd(s, 'show running-config interface gpon-onu_1/1/1:19', 3))

print('=== PON-ONU-MNG CONFIG ===')
print(cmd(s, 'show pon-onu-mng gpon-onu_1/1/1:19', 3))

print('=== ONU STATE ===')
print(cmd(s, 'show gpon onu state gpon-olt_1/1/1', 3))

print('=== ONU DETAIL ===')
print(cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:19', 3))

s.close()
