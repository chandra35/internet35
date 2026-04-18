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

# Get full running-config and grep for ONU 19 pon-onu-mng
print('=== RUNNING CONFIG SEARCH FOR ONU 19 ===')
full = cmd(s, 'show running-config | begin pon-onu-mng gpon-onu_1/1/1:19', 5)
# Just print first ~2000 chars to see ONU 19's pon-onu-mng section
print(full[:3000])

print('\n=== CONFIG STATE CHECK ===')
# Check if the ONU has proper OMCI config state
print(cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:19 | include Config', 3))

# Compare with a working ONU on same port
print('\n=== WORKING ONU 1 DETAIL ===')
print(cmd(s, 'show gpon onu detail-info gpon-onu_1/1/1:1 | include Config', 3))

s.close()
