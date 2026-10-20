<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use ZTE C320 specifically
$olt = App\Models\Olt::where('brand', 'zte')->first();
echo "OLT: {$olt->name} ({$olt->ip_address})\n";

$fp = @fsockopen($olt->ip_address, $olt->telnet_port ?? 23, $errno, $errstr, 10);
if (!$fp) die('Telnet failed: '.$errstr);

stream_set_timeout($fp, 2);

function waitFor($fp, $patterns, $timeout = 10) {
    $buf = '';
    $end = time() + $timeout;
    while (time() < $end) {
        $c = @fread($fp, 4096);
        if ($c) $buf .= $c;
        foreach ($patterns as $p) {
            if (stripos($buf, $p) !== false) return $buf;
        }
        usleep(100000);
    }
    return $buf;
}

function sendCmd($fp, $cmd, $timeout = 10) {
    fwrite($fp, $cmd . "\r\n");
    $buf = '';
    $end = time() + $timeout;
    while (time() < $end) {
        $c = @fread($fp, 8192);
        if ($c !== false && $c !== '') {
            $buf .= $c;
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

// Enable + no paging
echo sendCmd($fp, 'enable', 5) . "\n";
echo sendCmd($fp, 'terminal length 0', 3) . "\n";

// Show ONU config
echo "--- INTERFACE CONFIG ---\n";
echo sendCmd($fp, 'show running-config interface gpon-onu_1/1/1:19', 10) . "\n";

echo "--- PON-ONU-MNG CONFIG ---\n";
echo sendCmd($fp, 'show pon-onu-mng running-config gpon-onu_1/1/1:19', 15) . "\n";

fclose($fp);
