import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

stdin, stdout, stderr = ssh.exec_command("grep -A 10 -B 2 '17:00:' /www/wwwroot/internet35/storage/logs/laravel.log")
output = stdout.read().decode('utf-8', errors='ignore')
print("=== GREP 17:00: ===")
print(output if output else "No matches found for 17:00:")

# If no matches, let's search for the last 50 error entries in laravel.log
if not output:
    stdin, stdout, stderr = ssh.exec_command("grep -n 'ERROR' /www/wwwroot/internet35/storage/logs/laravel.log | tail -n 30")
    print(stdout.read().decode('utf-8', errors='ignore'))

ssh.close()
