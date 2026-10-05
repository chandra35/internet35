import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

script = r"""<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('email', 'superadmin@internet35.com')->first();
if ($u) {
    $u->password = Hash::make('password123');
    $u->save();
    echo "Password for superadmin@internet35.com set to password123\n";
}
$u2 = App\Models\User::where('email', 'admin@internet35.com')->first();
if ($u2) {
    $u2->password = Hash::make('password123');
    $u2->save();
    echo "Password for admin@internet35.com set to password123\n";
}
"""

stdin, stdout, stderr = ssh.exec_command("cat << 'EOF' > /www/wwwroot/internet35/scratch_reset.php\n" + script + "\nEOF\n")
stdout.read()

stdin, stdout, stderr = ssh.exec_command("/www/server/php/83/bin/php /www/wwwroot/internet35/scratch_reset.php")
output = stdout.read().decode('utf-8', errors='ignore')
print(output)

ssh.exec_command("rm /www/wwwroot/internet35/scratch_reset.php")
ssh.close()
