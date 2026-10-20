<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = App\Models\Onu::where('serial_number','HWTCD38C26AA')->first();
$olt = $o->olt;

$helper = new App\Helpers\Olt\ZteC320Helper();
// Try to use telnet helper
$host = $olt->ip_address;
$user = $olt->telnet_username ?: 'zte';
$pass = $olt->telnet_password ?: 'zte';
$port = $olt->telnet_port ?: 23;

echo "Connecting telnet to {$host}:{$port} as {$user}...\n";

$fp = @fsockopen($host, $port, $errno, $errstr, 8);
if(!$fp){ echo "FAIL: $errstr ($errno)\n"; exit; }
stream_set_timeout($fp, 5);

function readUntil($fp, $marker, $maxWait = 6) {
    $buf = ''; $start = time();
    while (time() - $start < $maxWait) {
        $chunk = fread($fp, 4096);
        if ($chunk === '' || $chunk === false) { usleep(100000); continue; }
        $buf .= $chunk;
        if (strpos($buf, $marker) !== false) return $buf;
    }
    return $buf;
}

readUntil($fp, 'sername:', 6);
fwrite($fp, $user."\r\n");
readUntil($fp, 'assword:', 6);
fwrite($fp, $pass."\r\n");
$out = readUntil($fp, '#', 6);
fwrite($fp, "terminal length 0\r\n");
readUntil($fp, '#', 4);

$cmds = [
    "show gpon onu state gpon-onu_1/1/1:16",
    "show gpon onu detail-info gpon-onu_1/1/1:16",
    "show running-config interface gpon-onu_1/1/1:16",
    "show running-config-pon-onu-mng gpon-onu_1/1/1:16",
];
foreach($cmds as $c){
    echo "\n========== $c ==========\n";
    fwrite($fp, $c."\r\n");
    $resp = readUntil($fp, '#', 8);
    echo $resp;
}
fwrite($fp, "exit\r\n");
fclose($fp);
echo "\nDONE\n";
