<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = App\Models\Olt::where('brand', 'zte')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

// Use executeBatchCliCommands-like approach
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

// Read until prompt
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

$cmds = [
    'enable',
    'terminal length 0',
    'configure terminal',
    'pon-onu-mng gpon-onu_1/1/4:2',
    'show running-config',
    'exit',
    'pon-onu-mng gpon-onu_1/1/1:19',
    'show running-config',
    'exit',
    'exit',
];

$output = '';
foreach ($cmds as $cmd) {
    fwrite($fp, $cmd . "\r\n");
    usleep(500000);
    $r = rp($fp, 20);
    $output .= "CMD[{$cmd}]:\n{$r}\n---\n";
}

fclose($fp);
echo $output;
