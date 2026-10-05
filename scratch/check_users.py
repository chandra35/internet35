import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

cmd = """/www/server/php/83/bin/php /www/wwwroot/internet35/artisan tinker --execute="foreach(App\\\\Models\\\\User::with('roles')->get() as \$u){ echo \$u->username . ' | ' . \$u->email . ' | Roles: ' . \$u->roles->pluck('name')->implode(',') . PHP_EOL; }" """

stdin, stdout, stderr = ssh.exec_command(cmd)
print(stdout.read().decode('utf-8', errors='ignore'))
err = stderr.read().decode('utf-8', errors='ignore')
if err:
    print("ERR:", err)

ssh.close()
