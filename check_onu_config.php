<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\Olt::first();
$fp = @fsockopen($olt->ip_address, $olt->telnet_port ?? 23, $errno, $errstr, 10);
if (!$fp) die('Telnet failed: '.$errstr);

stream_set_timeout($fp, 2);

function readUntil($fp, $patterns, $timeout = 10) {
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
function readPrompt($fp, $timeout = 10) {
    $buf = '';
    $end = time() + $timeout;
    while (time() < $end) {
        $c = @fread($fp, 4096);
        if ($c) $buf .= $c;
        if (preg_match('/[\w)][#>]\s*$/', $buf)) break;
        usleep(100000);
    }
    return $buf;
}

readUntil($fp, ['Username:', 'login:']);
fwrite($fp, $olt->telnet_username . "\r\n");
readUntil($fp, ['Password:']);
fwrite($fp, $olt->telnet_password . "\r\n");
sleep(1);
$loginOut = readPrompt($fp);

// Send all commands quickly
$cmds = [
    'enable',
    'terminal length 0',
    'show running-config interface gpon-onu_1/1/1:19',
    'show pon-onu-mng running-config gpon-onu_1/1/1:19',
];

foreach ($cmds as $cmd) {
    fwrite($fp, $cmd . "\r\n");
    sleep(2);
    // Read all available
    $buf = '';
    $start = time();
    while (time() - $start < 5) {
        $c = @fread($fp, 8192);
        if ($c !== false && $c !== '') {
            $buf .= $c;
            // Reset timer on data
            $start = time();
        }
        usleep(200000);
        // Check for prompt
        if (preg_match('/[\w)][#>]\s*$/', $buf)) break;
    }
    echo "CMD[$cmd]: " . trim($buf) . "\n---\n";
}
fclose($fp);
