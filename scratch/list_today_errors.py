import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

cmd = """python3 -c "
with open('/www/wwwroot/internet35/storage/logs/laravel.log') as f:
    for line in f:
        if line.startswith('[2026-10-05'):
            print(line.strip())
" """

stdin, stdout, stderr = ssh.exec_command(cmd)
print(stdout.read().decode('utf-8', errors='ignore'))

ssh.close()
