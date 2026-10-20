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
cmd(s,'configure terminal',1)
cmd(s,'pon-onu-mng gpon-onu_1/1/1:19',1)

print('=== VLAN PORT HELP ===', flush=True)
print(cmd(s,'vlan port ?',2), flush=True)

print('=== IP-HOST HELP ===', flush=True)
print(cmd(s,'ip-host ?',2), flush=True)

print('=== IP-HOST 1 HELP ===', flush=True)
print(cmd(s,'ip-host 1 ?',2), flush=True)

print('=== WAN HELP ===', flush=True)
print(cmd(s,'wan ?',2), flush=True)

print('=== TR069-MGMT HELP ===', flush=True)
print(cmd(s,'tr069-mgmt ?',2), flush=True)

print('=== SHOW RUNNING CONFIG ONU 19 ===', flush=True)
cmd(s,'exit',1)
cmd(s,'exit',1)
print(cmd(s,'show running-config interface gpon-onu_1/1/1:19',5), flush=True)

s.close()
