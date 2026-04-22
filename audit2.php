<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$user = $olt->telnet_username;
$pass = $olt->telnet_password;
function waitFor($fp,$patterns,$timeout=10){$buf='';$start=time();while(time()-$start<$timeout){$data=@fread($fp,4096);if($data)$buf.=$data;foreach($patterns as $p){if(strpos($buf,$p)!==false)return $buf;}if(stream_get_meta_data($fp)['timed_out'])usleep(100000);}return $buf;}
function readPrompt($fp,$timeout=15){$buf='';$deadline=time()+$timeout;while(time()<$deadline){$data=@fread($fp,4096);if($data)$buf.=$data;if(preg_match('/[\w)][#>]\s*$/',$buf))break;if(stream_get_meta_data($fp)['timed_out'])usleep(100000);}return $buf;}
$fp=fsockopen('136.1.1.100',23,$e,$es,10);stream_set_timeout($fp,2);
waitFor($fp,['Username:','login:','>']);fwrite($fp,$user."\r\n");
waitFor($fp,['Password:','password:']);fwrite($fp,$pass."\r\n");
sleep(1);readPrompt($fp);fwrite($fp,"terminal length 0\r\n");usleep(500000);readPrompt($fp);

function cmd($fp,$c){fwrite($fp,$c."\r\n");usleep(300000);$o=readPrompt($fp,8);echo"\n=== ".$c." ===\n".preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$o);}

// VLAN 111 details
cmd($fp,'show vlan 111');
// DHCP server pools
cmd($fp,'show ip dhcp pool');
// DHCP snooping
cmd($fp,'show ip dhcp snooping');
// ONU 16 ip-host binding  
cmd($fp,'show gpon remote-unit ip-host gpon-onu_1/1/1:16 1');
cmd($fp,'show gpon remote-unit ip-host gpon-onu_1/1/1:16 2');
// ONU 16 pon-onu-mng running config (current state)
fwrite($fp,"configure terminal\r\n");usleep(300000);readPrompt($fp);
fwrite($fp,"pon-onu-mng gpon-onu_1/1/1:16\r\n");usleep(300000);readPrompt($fp);
cmd($fp,'show config');
fwrite($fp,"exit\r\n");usleep(200000);readPrompt($fp);
fwrite($fp,"exit\r\n");usleep(200000);readPrompt($fp);
fclose($fp);