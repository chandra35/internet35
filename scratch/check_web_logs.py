import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

def run_cmd(cmd):
    print(f"=== {cmd} ===")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode('utf-8', errors='ignore'))
    err = stderr.read().decode('utf-8', errors='ignore')
    if err:
        print("ERR:", err)

run_cmd("tail -n 30 /www/wwwlogs/172.16.2.4.error.log")
run_cmd("tail -n 30 /www/server/php/83/var/log/php-fpm.log")
run_cmd("tail -n 30 /www/wwwlogs/nginx_error.log")

ssh.close()
