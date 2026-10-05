import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

# Fetch the last 500 lines and print only lines starting with [2026-10-05 or exception messages
stdin, stdout, stderr = ssh.exec_command('tail -n 500 /www/wwwroot/internet35/storage/logs/laravel.log')
content = stdout.read().decode('utf-8', errors='ignore')

lines = content.splitlines()
for i, line in enumerate(lines):
    if '[2026-10-05' in line:
        print("--- HEADER ---")
        for j in range(i, min(i + 15, len(lines))):
            print(lines[j])

ssh.close()
