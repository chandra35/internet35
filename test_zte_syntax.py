import socket, time, sys

def telnet_cmd(sock, cmd, wait=2):
    sock.send((cmd + '\r\n').encode())
    time.sleep(wait)
    data = b''
    sock.setblocking(False)
    try:
        while True:
            chunk = sock.recv(4096)
            if not chunk: break
            data += chunk
    except: pass
    sock.setblocking(True)
    return data.decode('ascii', errors='ignore')

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

telnet_cmd(s, 'terminal length 0', 1)
telnet_cmd(s, 'configure terminal', 1)

# Test OLT interface format
print('=== TEST: interface gpon-olt_1/1/1 ===')
r = telnet_cmd(s, 'interface gpon-olt_1/1/1', 2)
print(r)

print('=== OLT prompt ===')
r = telnet_cmd(s, '', 1)
print('PROMPT:', repr(r.strip()))

# Check onu registration command syntax
print('=== onu ? ===')
r = telnet_cmd(s, 'onu ?', 2)
print(r)

telnet_cmd(s, 'exit', 1)

# Check traffic profiles
telnet_cmd(s, 'exit', 1)
print('=== TRAFFIC PROFILES ===')
r = telnet_cmd(s, 'show gpon profile tcont', 5)
print(r)

print('=== VLAN PROFILES ===')
r = telnet_cmd(s, 'show gpon profile vlan', 5)
print(r)

telnet_cmd(s, 'exit', 1)
s.close()
print('=== DONE ===')
