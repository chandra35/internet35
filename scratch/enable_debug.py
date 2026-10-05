import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

# Set APP_DEBUG=true
stdin, stdout, stderr = ssh.exec_command("sed -i 's/APP_DEBUG=false/APP_DEBUG=true/g' /www/wwwroot/internet35/.env")
stdout.read()

# Clear config cache
stdin, stdout, stderr = ssh.exec_command("/www/server/php/83/bin/php /www/wwwroot/internet35/artisan config:clear")
stdout.read()

print("APP_DEBUG is now TRUE. Config cleared.")
ssh.close()
