<?php
// Minimal script - just get SmartOLT ONU config
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\Olt::where('brand', 'zte')->first();
$fp = @fsockopen($olt->ip_address, 23, $errno, $errstr, 10);
if (!$fp) die('fail');
stream_set_timeout($fp, 5);

function r($fp, $t=10) {
    $b=''; $e=time()+$t;
    while(time()<$e) {
        $c=@fread($fp,8192);
        if($c!==false&&$c!=='') $b.=$c;
        if(preg_match('/[#>]\s*$/',$b)) return $b;
        usleep(100000);
    }
    return $b;
}

// Login + enable
$b = r($fp); fwrite($fp, $olt->telnet_username."\r\n");
$b = r($fp); fwrite($fp, $olt->telnet_password."\r\n");
r($fp);
fwrite($fp, "enable\r\n"); r($fp);
fwrite($fp, "terminal length 0\r\n"); r($fp);
fwrite($fp, "configure terminal\r\n"); r($fp);

// Enter pon-onu-mng for SmartOLT ONU
fwrite($fp, "pon-onu-mng gpon-onu_1/1/4:2\r\n"); r($fp);
fwrite($fp, "show running-config\r\n");
echo "=== SmartOLT 1/1/4:2 ===\n" . r($fp, 15) . "\n";
fwrite($fp, "exit\r\n"); r($fp);

// Enter pon-onu-mng for our ONU
fwrite($fp, "pon-onu-mng gpon-onu_1/1/1:19\r\n"); r($fp);
fwrite($fp, "show running-config\r\n");
echo "=== Our App 1/1/1:19 ===\n" . r($fp, 15) . "\n";
fwrite($fp, "exit\r\n"); r($fp);

fclose($fp);
