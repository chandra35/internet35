import socket, time, sys

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

print('=== ONU TYPES ===', flush=True)
print(cmd(s,'show onu-type gpon',3), flush=True)

print('=== EXISTING ONU 2 TYPE ===', flush=True)
print(cmd(s,'show gpon onu baseinfo gpon-olt_1/1/1',5), flush=True)

s.close()
