import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('172.16.2.4', username='root', password='kosongkosong')

script = r"""<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::all();
foreach ($users as $user) {
    Auth::loginUsingId($user->id);
    $request = Illuminate\Http\Request::create('/admin/dashboard', 'GET');
    try {
        $response = $kernel->handle($request);
        echo "User: {$user->username} ({$user->email}) | Status: {$response->getStatusCode()}\n";
        if ($response->getStatusCode() == 500) {
            echo "RESPONSE BODY SUBSTR:\n" . substr($response->getContent(), 0, 1000) . "\n";
        }
    } catch (\Throwable $e) {
        echo "User: {$user->username} | EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
"""

stdin, stdout, stderr = ssh.exec_command("cat << 'EOF' > /www/wwwroot/internet35/scratch_test.php\n" + script + "\nEOF\n")
stdout.read()

stdin, stdout, stderr = ssh.exec_command("/www/server/php/83/bin/php /www/wwwroot/internet35/scratch_test.php")
output = stdout.read().decode('utf-8', errors='ignore')
print(output)

ssh.exec_command("rm /www/wwwroot/internet35/scratch_test.php")
ssh.close()
