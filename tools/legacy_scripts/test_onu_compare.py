import socket, time

def cmd(s, c, w=2):
    s.send((c+'\r\n').encode())
    time.sleep(w)
    d=b''
    s.setblocking(False)
    try:
        while True:
            ch=s.recv(4096)
            if not ch: break
            d+=ch
    except: pass
    s.setblocking(True)
    return d.decode('ascii',errors='ignore')

s=socket.socket()
s.settimeout(10)
s.connect(('136.1.1.100',23))
time.sleep(1); s.recv(4096)
s.send(b'zte\r\n'); time.sleep(1); s.recv(4096)
s.send(b'zte\r\n'); time.sleep(2); s.recv(4096)
cmd(s,'terminal length 0',1)

# Show pon-onu-mng config for existing ONU 2 (known working Huawei)
print('=== ONU 2 FULL CONFIG ===', flush=True)
print(cmd(s,'show pon-onu-mng gpon-onu_1/1/1:2',5), flush=True)

# Show pon-onu-mng config for our new ONU 19
print('=== ONU 19 FULL CONFIG ===', flush=True)
print(cmd(s,'show pon-onu-mng gpon-onu_1/1/1:19',5), flush=True)

# Also check show gpon onu baseinfo for ONU 2 and 19
print('=== ONU 2 BASEINFO ===', flush=True)
print(cmd(s,'show gpon onu baseinfo gpon-onu_1/1/1:2',3), flush=True)

print('=== ONU 19 BASEINFO ===', flush=True)
print(cmd(s,'show gpon onu baseinfo gpon-onu_1/1/1:19',3), flush=True)

s.close()
