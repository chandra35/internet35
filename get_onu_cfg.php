<?php
// Get specific ONU pon-onu-mng config by parsing full dump
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\Olt::where('brand', 'zte')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

// Use the helper's telnet to run a targeted command
$fp = @fsockopen($olt->ip_address, 23, $errno, $errstr, 10);
if (!$fp) die('fail');
stream_set_timeout($fp, 5);

$all = '';
$deadline = time() + 10;
while (time() < $deadline) {
    $c = @fread($fp, 4096);
    if ($c) $all .= $c;
    if (stripos($all, 'Username:') !== false || stripos($all, 'login:') !== false) break;
    usleep(100000);
}
fwrite($fp, $olt->telnet_username . "\r\n");
$all = '';
$deadline = time() + 10;
while (time() < $deadline) {
    $c = @fread($fp, 4096);
    if ($c) $all .= $c;
    if (stripos($all, 'Password:') !== false) break;
    usleep(100000);
}
fwrite($fp, $olt->telnet_password . "\r\n");
sleep(1);

function rp($fp, $t = 15) {
    $b = ''; $d = time() + $t;
    while (time() < $d) {
        $c = @fread($fp, 4096);
        if ($c === false || $c === '') { usleep(100000); continue; }
        $b .= $c;
        if (preg_match('/[\w)][#>]\s*$/', $b)) break;
    }
    return $b;
}

rp($fp);

// Simple commands
foreach (['enable', 'terminal length 0'] as $cmd) {
    fwrite($fp, $cmd . "\r\n");
    usleep(300000);
    rp($fp);
}

// Use "show run | include" style or just get the specific interface
// Try: show running-config interface gpon-onu_1/1/4:2
fwrite($fp, "show running-config interface gpon-onu_1/1/4:2\r\n");
usleep(500000);
$out = rp($fp, 20);
echo "=== INTERFACE gpon-onu_1/1/4:2 ===\n";
echo trim($out) . "\n\n";

// For pon-onu-mng, we need to enter config mode and use a pipe filter
fwrite($fp, "configure terminal\r\n");
usleep(300000);
rp($fp);

// show running-config with section filter
fwrite($fp, "show running-config | section gpon-onu_1/1/4:2\r\n");
usleep(500000);
$out = rp($fp, 20);
echo "=== SECTION gpon-onu_1/1/4:2 ===\n";
echo trim($out) . "\n\n";

fwrite($fp, "exit\r\n");
rp($fp);
fclose($fp);
