<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\Olt::where('brand', 'zte')->first();
echo "OLT: {$olt->name} ({$olt->ip_address})\n\n";

$fp = @fsockopen($olt->ip_address, $olt->telnet_port ?? 23, $errno, $errstr, 10);
if (!$fp) die('Telnet failed: '.$errstr);
stream_set_timeout($fp, 2);

function waitFor($fp, $patterns, $timeout = 10) {
    $buf = '';
    $end = time() + $timeout;
    while (time() < $end) {
        $c = @fread($fp, 4096);
        if ($c) $buf .= $c;
        foreach ($patterns as $p) if (stripos($buf, $p) !== false) return $buf;
        usleep(100000);
    }
    return $buf;
}

function sendCmd($fp, $cmd, $timeout = 15) {
    fwrite($fp, $cmd . "\r\n");
    sleep(1);
    $buf = '';
    $end = time() + $timeout;
    while (time() < $end) {
        $c = @fread($fp, 8192);
        if ($c !== false && $c !== '') {
            $buf .= $c;
            // Keep reading if data is coming
            usleep(200000);
            continue;
        }
        usleep(200000);
        if (preg_match('/[\w)][#>]\s*$/', $buf)) break;
    }
    return $buf;
}

// Login
waitFor($fp, ['Username:', 'login:']);
fwrite($fp, $olt->telnet_username . "\r\n");
waitFor($fp, ['Password:']);
fwrite($fp, $olt->telnet_password . "\r\n");
sleep(1);
sendCmd($fp, '', 5);
sendCmd($fp, 'enable', 5);
sendCmd($fp, 'terminal length 0', 3);

// Compare ONU 1/1/4:2 (SmartOLT reference) vs ONU 1/1/1:19 (our app)
$onus = [
    ['label' => 'SmartOLT (1/1/4:2)', 'intf' => 'gpon-onu_1/1/4:2'],
    ['label' => 'Our App (1/1/1:19)', 'intf' => 'gpon-onu_1/1/1:19'],
];

foreach ($onus as $onu) {
    echo "============================================\n";
    echo "=== {$onu['label']} - PON-ONU-MNG CONFIG ===\n";
    echo "============================================\n";
    $out = sendCmd($fp, "show pon-onu-mng running-config {$onu['intf']}", 15);
    echo trim($out) . "\n\n";

    echo "============================================\n";
    echo "=== {$onu['label']} - RUNNING CONFIG ===\n";
    echo "============================================\n";
    $out = sendCmd($fp, "configure terminal", 5);
    $out = sendCmd($fp, "show running-config interface {$onu['intf']}", 15);
    echo trim($out) . "\n\n";
    sendCmd($fp, "exit", 3);
}

fclose($fp);
