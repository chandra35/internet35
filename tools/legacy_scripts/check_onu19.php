<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fp = @fsockopen('136.1.1.100', 23, $errno, $errstr, 5);
if (!$fp) die("Telnet failed: $errstr\n");
stream_set_timeout($fp, 2);

function readUntil($fp, $timeout = 10) {
    $buf = '';
    $deadline = time() + $timeout;
    while (time() < $deadline) {
        $c = @fread($fp, 4096);
        if ($c === false || $c === '') { usleep(100000); continue; }
        $buf .= $c;
        if (preg_match('/[\w)][#>]\s*$/', $buf)) break;
    }
    return $buf;
}

function waitFor($fp, $patterns, $timeout = 10) {
    $buf = '';
    $deadline = time() + $timeout;
    while (time() < $deadline) {
        $c = @fread($fp, 4096);
        if ($c === false || $c === '') { usleep(100000); continue; }
        $buf .= $c;
        foreach ($patterns as $p) {
            if (stripos($buf, $p) !== false) return $buf;
        }
    }
    return $buf;
}

waitFor($fp, ['Username:', 'login:']);
fwrite($fp, "zte\r\n");
waitFor($fp, ['Password:']);
fwrite($fp, "zte\r\n");
sleep(1);
readUntil($fp);

fwrite($fp, "terminal length 0\r\n");
usleep(500000);
readUntil($fp);

// Check interface config
fwrite($fp, "show running-config interface gpon-onu_1/1/1:19\r\n");
usleep(500000);
$iface = readUntil($fp, 15);
echo "=== INTERFACE CONFIG ===\n";
echo $iface . "\n\n";

// Check pon-onu-mng config
fwrite($fp, "show onu running config gpon-onu_1/1/1:19\r\n");
usleep(500000);
$ponmng = readUntil($fp, 15);
echo "=== PON-ONU-MNG CONFIG ===\n";
echo $ponmng . "\n";

fclose($fp);
