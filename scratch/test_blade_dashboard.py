import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

script = r"""<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'superadmin@internet35.com')->first();
Auth::login($user);

try {
    $controller = new App\Http\Controllers\Admin\DashboardController();
    $res = $controller->index();
    $html = $res->render();
    echo "DashboardController->index() success! HTML length: " . strlen($html) . "\n";
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
"""

stdin, stdout, stderr = ssh.exec_command("cat << 'EOF' > /www/wwwroot/internet35/scratch_test_blade.php\n" + script + "\nEOF\n")
stdout.read()

stdin, stdout, stderr = ssh.exec_command("/www/server/php/83/bin/php /www/wwwroot/internet35/scratch_test_blade.php")
output = stdout.read().decode('utf-8', errors='ignore')
print(output)

ssh.exec_command("rm /www/wwwroot/internet35/scratch_test_blade.php")
ssh.close()
