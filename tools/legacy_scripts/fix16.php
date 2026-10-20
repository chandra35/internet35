<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$user = $olt->telnet_username;
$pass = $olt->telnet_password;
function waitFor($fp,$p,$t=10){$b='';$s=time();while(time()-$s<$t){$d=@fread($fp,4096);if($d)$b.=$d;foreach($p as $x){if(strpos($b,$x)!==false)return $b;}if(stream_get_meta_data($fp)['timed_out'])usleep(100000);}return $b;}
function rp($fp,$t=15){$b='';$d=time()+$t;while(time()<$d){$x=@fread($fp,4096);if($x)$b.=$x;if(preg_match('/[\w)][#>]\s*$/',$b))break;if(stream_get_meta_data($fp)['timed_out'])usleep(100000);}return $b;}
$fp=fsockopen('136.1.1.100',23,$e,$es,10);stream_set_timeout($fp,2);
waitFor($fp,['Username:','login:','>']);fwrite($fp,$user."\r\n");
waitFor($fp,['Password:','password:']);fwrite($fp,$pass."\r\n");
sleep(1);rp($fp);fwrite($fp,"terminal length 0\r\n");usleep(500000);rp($fp);

$cmds = [
    'configure terminal',
    'pon-onu-mng gpon-onu_1/1/1:16',
    'flow 2 switch switch_0/1',
    'flow mode 2 tag-filter vlan-filter untag-filter discard',
    'flow 2 pri 2 vlan 111',
    'gemport 2 flow 2',
    'dhcp-ip ethuni eth_0/1 from-onu',
    'dhcp-ip ethuni eth_0/2 from-onu',
    'dhcp-ip ethuni eth_0/3 from-onu',
    'dhcp-ip ethuni eth_0/4 from-onu',
    'security-mgmt 998 state enable mode forward ingress-type lan protocol web https',
    'security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069',
    'exit',
    'exit',
    'write',
];
foreach($cmds as $cmd) {
    fwrite($fp,$cmd."\r\n");
    usleep(500000);
    $out = rp($fp,5);
    $clean = preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$out);
    echo "CMD: " . $cmd . "\n  >> " . trim(substr($clean,-50)) . "\n";
}
fclose($fp);