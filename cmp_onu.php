<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\Olt::where('brand', 'zte')->first();
$fp = @fsockopen($olt->ip_address, $olt->telnet_port ?? 23, $errno, $errstr, 10);
if (!$fp) die('Telnet failed');
stream_set_timeout($fp, 3);

function rd($fp, $wait, $timeout = 15) {
    $buf = '';
    $end = time() + $timeout;
    while (time() < $end) {
        $c = @fread($fp, 8192);
        if ($c !== false && $c !== '') $buf .= $c;
        foreach ((array)$wait as $w) if (stripos($buf, $w) !== false) return $buf;
        usleep(150000);
    }
    return $buf;
}

// Login
rd($fp, ['Username:', 'login:']);
fwrite($fp, $olt->telnet_username . "\r\n");
rd($fp, ['Password:']);
fwrite($fp, $olt->telnet_password . "\r\n");
rd($fp, ['>', '#']);

fwrite($fp, "enable\r\n");
rd($fp, '#');
fwrite($fp, "terminal length 0\r\n");
rd($fp, '#');
fwrite($fp, "configure terminal\r\n");
rd($fp, '#');

// Get SmartOLT reference: running-config for interface
fwrite($fp, "show running-config interface gpon-onu_1/1/4:2\r\n");
$out1 = rd($fp, '#', 20);
echo "=== SmartOLT 1/1/4:2 INTERFACE ===\n";
echo $out1 . "\n";

// Get pon-onu-mng config
fwrite($fp, "pon-onu-mng gpon-onu_1/1/4:2\r\n");
rd($fp, '#');
fwrite($fp, "show running-config\r\n");
$out2 = rd($fp, '#', 20);
echo "=== SmartOLT 1/1/4:2 PON-ONU-MNG ===\n";
echo $out2 . "\n";
fwrite($fp, "exit\r\n");
rd($fp, '#');

// Our app ONU
fwrite($fp, "show running-config interface gpon-onu_1/1/1:19\r\n");
$out3 = rd($fp, '#', 20);
echo "=== Our App 1/1/1:19 INTERFACE ===\n";
echo $out3 . "\n";

fwrite($fp, "pon-onu-mng gpon-onu_1/1/1:19\r\n");
rd($fp, '#');
fwrite($fp, "show running-config\r\n");
$out4 = rd($fp, '#', 20);
echo "=== Our App 1/1/1:19 PON-ONU-MNG ===\n";
echo $out4 . "\n";
fwrite($fp, "exit\r\n");
rd($fp, '#');

fclose($fp);
