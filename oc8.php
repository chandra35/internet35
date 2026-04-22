<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$user = $olt->telnet_username;
$pass = $olt->telnet_password;

function waitFor($fp, $patterns, $timeout=10) {
    $buf=''; $start=time();
    while(time()-$start < $timeout) {
        $data=@fread($fp,4096); if($data) $buf.=$data;
        foreach($patterns as $p) { if(strpos($buf,$p)!==false) return $buf; }
        if(stream_get_meta_data($fp)['timed_out']) usleep(100000);
    } return $buf;
}
function readPrompt($fp, $timeout=15) {
    $buf=''; $deadline=time()+$timeout;
    while(time()<$deadline) {
        $data=@fread($fp,4096); if($data) $buf.=$data;
        if(preg_match('/[\w)][#>]\s*$/',$buf)) break;
        if(stream_get_meta_data($fp)['timed_out']) usleep(100000);
    } return $buf;
}
$fp=fsockopen('136.1.1.100',23,$errno,$errstr,10);
stream_set_timeout($fp,2);
waitFor($fp,['Username:','login:','>']);
fwrite($fp,$user."\r\n");
waitFor($fp,['Password:','password:']);
fwrite($fp,$pass."\r\n");
sleep(1); readPrompt($fp);
fwrite($fp,"terminal length 0\r\n"); usleep(500000); readPrompt($fp);

// Try different show commands
$cmds = [
    'show gpon onu detail-info gpon-onu_1/1/1:16',
    'show gpon onu cfg gpon-onu_1/1/1:16',
    'show gpon onu traffic-info gpon-onu_1/1/1:16',
];
foreach($cmds as $cmd) {
    fwrite($fp,$cmd."\r\n");
    $out = readPrompt($fp,10);
    echo "\n=== $cmd ===\n" . preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',"");
}
fclose($fp);