<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
echo "telnet_user: " . $olt->telnet_username . "\n";
echo "telnet_pass_len: " . strlen($olt->telnet_password) . "\n";
echo "enable_pass_len: " . strlen($olt->enable_password ?? '') . "\n";
// Test telnet
$sock = fsockopen('136.1.1.100', 23, $errno, $errstr, 10);
if (!$sock) { echo "Telnet failed: $errstr\n"; exit; }
stream_set_timeout($sock, 3);
$buf = ''; $start = time();
while(time() - $start < 5) {
    $data = fread($sock, 4096);
    if ($data) $buf .= $data;
    if (stream_get_meta_data($sock)['timed_out']) break;
}
echo "Login prompt recv: " . strlen($buf) . " bytes\n";
echo bin2hex(substr($buf, 0, 30)) . "\n";
fclose($sock);