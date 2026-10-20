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

// 1. ONU 16 ip-host status (DHCP result)
fwrite($fp,"show gpon onu ip-host gpon-onu_1/1/1:16\r\n");
$out1 = readPrompt($fp,8);
echo "=== IP-HOST DHCP STATUS ===\n" . preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$out1);

// 2. Check VLAN 111 service-port / DHCP relay config
fwrite($fp,"show service-port interface gpon-onu_1/1/1:16\r\n");
$out2 = readPrompt($fp,8);
echo "\n=== SERVICE-PORT (ONU 16) ===\n" . preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$out2);

// 3. Check DHCP relay for vlan 111
fwrite($fp,"show ip dhcp relay\r\n");
$out3 = readPrompt($fp,8);
echo "\n=== DHCP RELAY ===\n" . preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$out3);

// 4. Check service-port with vlan 111
fwrite($fp,"show service-port vlan 111\r\n");
$out4 = readPrompt($fp,8);
echo "\n=== SERVICE-PORT VLAN 111 ===\n" . preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$out4);

fclose($fp);