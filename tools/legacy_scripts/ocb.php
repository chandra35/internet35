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

// Step by step apply
$steps = [
    'configure terminal',
    'pon-onu-mng gpon-onu_1/1/1:16',
    'flow mode 1 tag-filter vlan-filter untag-filter discard',
    'flow mode 2 tag-filter vlan-filter untag-filter discard',
    'flow 1 pri 0 vlan 335',
    'flow 2 pri 2 vlan 111',
    'gemport 1 flow 1',
    'gemport 2 flow 2',
    'switchport-bind switch_0/1 iphost 1',
    'switchport-bind switch_0/1 iphost 2',
    'switchport-bind switch_0/1 veip 1',
    'ip-host 2 dhcp-enable enable ping-response enable traceroute-response enable',
    'vlan-filter-mode iphost 1 tag-filter vlan-filter untag-filter discard',
    'vlan-filter-mode iphost 2 tag-filter vlan-filter untag-filter discard',
    'vlan-filter iphost 1 pri 0 vlan 335',
    'vlan-filter iphost 2 pri 2 vlan 111',
    'dhcp-ip ethuni eth_0/1 from-onu',
    'dhcp-ip ethuni eth_0/2 from-onu',
    'dhcp-ip ethuni eth_0/3 from-onu',
    'dhcp-ip ethuni eth_0/4 from-onu',
    'veip 1 port udp 1232 host 2',
    'tr069-mgmt 1 state unlock',
    'tr069-mgmt 1 acs http://172.10.10.254:7547',
    'tr069-mgmt 1 tag pri 2 vlan 111',
    'security-mgmt 998 state enable mode forward ingress-type lan protocol web https',
    'security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069',
    'exit',
    'exit',
    'write',
];
foreach($steps as $cmd) {
    fwrite($fp,$cmd."\r\n");
    usleep(400000);
    $out = readPrompt($fp,5);
    $clean = preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$out);
    echo "CMD: " . $cmd . "\n";
    if(strpos($clean,'%Error')!==false || strpos($clean,'invalid')!==false || strpos($clean,'Invalid')!==false) {
        echo "  >> ERROR: " . trim($clean) . "\n";
    } else {
        echo "  >> OK (prompt: " . trim(substr($clean,-20)) . ")\n";
    }
}
fclose($fp);