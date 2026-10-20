<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$host = $olt->ip_address;
$user = $olt->telnet_username ?? 'admin';
$pass = $olt->telnet_password;
$sock = fsockopen($host, 23, $errno, $errstr, 10);
if (!$sock) { die("Telnet failed: $errstr"); }
stream_set_timeout($sock, 5);
function readAll($sock) {
    $out = ''; while (!feof($sock)) {
        $buf = fread($sock, 4096);
        if ($buf === false || $buf === '') break;
        $out .= $buf;
        if (stream_get_meta_data($sock)['timed_out']) break;
    } return $out;
}
function send($sock, $cmd, $wait=800000) {
    fwrite($sock, $cmd . "\n"); usleep($wait); return readAll($sock);
}
readAll($sock);
send($sock, $user); send($sock, $pass);
send($sock, 'enable'); send($sock, $pass);
send($sock, 'terminal length 0');
echo "=== GPON ONU DETAIL ===\n";
echo send($sock, 'show gpon onu detail-info gpon-onu_1/1/1:16', 1500000);
echo "\n=== PON-ONU-MNG ===\n";
echo send($sock, 'show pon-onu-mng gpon-onu_1/1/1:16', 2000000);
fclose($sock);