import socket, time, re, sys

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

# Check firmware version
print('=== FIRMWARE VERSION ===')
print(telnet_cmd(s, 'show version', 3))

# Check unconfigured ONUs
print('=== UNCONFIGURED ONUs ===')
print(telnet_cmd(s, 'show gpon onu uncfg', 3))

s.close()
