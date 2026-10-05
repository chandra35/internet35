import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

print("=== internet35.log ===")
stdin, stdout, stderr = ssh.exec_command("tail -n 30 /www/wwwlogs/internet35.log")
print(stdout.read().decode('utf-8', errors='ignore'))

print("=== laravel.log (last 100 lines) ===")
stdin, stdout, stderr = ssh.exec_command("tail -n 100 /www/wwwroot/internet35/storage/logs/laravel.log")
print(stdout.read().decode('utf-8', errors='ignore'))

ssh.close()
