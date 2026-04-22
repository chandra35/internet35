<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();

// Direct telnet to check running config
$host = $olt->ip_address;
$port = 23;
$user = $olt->telnet_username ?? 'admin';
$pass = $olt->telnet_password;

$sock = fsockopen($host, $port, $errno, $errstr, 10);
if (!$sock) { die("Telnet failed: $errstr"); }
stream_set_timeout($sock, 5);

function readAll($sock) {
    $out = '';
    while (!feof($sock)) {
        $buf = fread($sock, 4096);
        if ($buf === false || $buf === '') break;
        $out .= $buf;
        $info = stream_get_meta_data($sock);
        if ($info['timed_out']) break;
    }
    return $out;
}

function send($sock, $cmd) {
    fwrite($sock, $cmd . "\n");
    usleep(800000);
    return readAll($sock);
}

// Login
readAll($sock);
send($sock, $user);
send($sock, $pass);
send($sock, 'enable');
send($sock, $pass);
send($sock, 'terminal length 0');

// Check running config
$out = send($sock, 'show running-config gpon-onu_1/1/1:16');
echo "=== RUNNING CONFIG ===\n";
echo $out;

// Check pon-onu-mng
$out2 = send($sock, 'show pon-onu-mng gpon-onu_1/1/1:16');
echo "\n=== PON-ONU-MNG ===\n";
echo $out2;

fclose($sock);