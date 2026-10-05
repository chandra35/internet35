import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

stdin, stdout, stderr = ssh.exec_command("ls -la /www/wwwlogs/")
print(stdout.read().decode('utf-8', errors='ignore'))

ssh.close()
