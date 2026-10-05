import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

stdin, stdout, stderr = ssh.exec_command("grep -A 20 '\[2026-10-05 10:' /www/wwwroot/internet35/storage/logs/laravel.log")
output = stdout.read().decode('utf-8', errors='ignore')
print("=== GREP 10: ===")
print(output if output else "No matches found for 10:")

if not output:
    # Let's read the very last 100 lines of laravel.log
    stdin, stdout, stderr = ssh.exec_command("tail -n 100 /www/wwwroot/internet35/storage/logs/laravel.log")
    print("=== TAIL 100 ===")
    print(stdout.read().decode('utf-8', errors='ignore'))

ssh.close()
