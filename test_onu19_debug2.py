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
cmd(s,'configure terminal',1)
cmd(s,'pon-onu-mng gpon-onu_1/1/1:19',1)

print('=== ip-host 1 dhcp-enable ? ===', flush=True)
print(cmd(s,'ip-host 1 dhcp-enable ?',2), flush=True)

print('=== vlan port eth_0/1 ? ===', flush=True)
print(cmd(s,'vlan port eth_0/1 ?',2), flush=True)

print('=== vlan port veip_0/1 ? ===', flush=True)
print(cmd(s,'vlan port veip_0/1 ?',2), flush=True)

# Check existing ONU 2 pon-onu-mng config
cmd(s,'exit',1)
print('=== ONU 2 PON-ONU-MNG CONFIG ===', flush=True)
cmd(s,'pon-onu-mng gpon-onu_1/1/1:2',1)
print(cmd(s,'show this',3), flush=True)

cmd(s,'exit',1)
cmd(s,'exit',1)
s.close()
