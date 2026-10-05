import subprocess
import paramiko

def run_local(cmd):
    print(f"[LOCAL] {cmd}")
    res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    if res.stdout:
        print(res.stdout)
    if res.stderr:
        print(res.stderr)

run_local("git add .")
run_local('git commit -m "fix(auth): prevent redirect loops on UnauthorizedException and restore app debug config"')
run_local("git push origin main")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

def run_ssh(cmd):
    print(f"[VM] {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='ignore')
    err = stderr.read().decode('utf-8', errors='ignore')
    if out:
        print(out)
    if err:
        print("ERR:", err)

run_ssh("cd /www/wwwroot/internet35 && git pull origin main")
run_ssh("/www/server/php/83/bin/php /www/wwwroot/internet35/artisan view:clear")
run_ssh("/www/server/php/83/bin/php /www/wwwroot/internet35/artisan route:clear")
run_ssh("/www/server/php/83/bin/php /www/wwwroot/internet35/artisan config:clear")
run_ssh("/www/server/php/83/bin/php /www/wwwroot/internet35/artisan cache:clear")

run_ssh("systemctl restart php-fpm-83")

ssh.close()

print("DEPLOYMENT COMPLETE!")
