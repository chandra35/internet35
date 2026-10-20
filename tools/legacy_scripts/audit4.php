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
function c($fp,$cmd,$t=8){fwrite($fp,$cmd."\r\n");usleep(400000);$o=rp($fp,$t);echo "\n=== ".$cmd." ===\n".preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$o);}
$fp=fsockopen('136.1.1.100',23,$e,$es,10);stream_set_timeout($fp,2);
waitFor($fp,['Username:','login:','>']);fwrite($fp,$user."\r\n");
waitFor($fp,['Password:','password:']);fwrite($fp,$pass."\r\n");
sleep(1);rp($fp);fwrite($fp,"terminal length 0\r\n");usleep(500000);rp($fp);

// 1. Cek apakah ONU 16 sudah punya IP via DHCP (ip-host result)
c($fp,'show pon-onu-mng gpon-onu_1/1/1:16 ip-host');
c($fp,'show pon-onu-mng gpon-onu_1/1/1:16');
// 2. Cek service-port (apakah vlan 111 ada)
c($fp,'show service-port interface gpon-onu_1/1/1:16');
// 3. Cek mac address learned from this ONU
c($fp,'show mac vlan 111 | include 1/1/1:16');
c($fp,'show mac interface gpon-onu_1/1/1:16');
// 4. Compare with ONU 17 (working Huawei)  
c($fp,'show pon-onu-mng gpon-onu_1/1/1:17 ip-host');
c($fp,'show mac interface gpon-onu_1/1/1:17');
// 5. Cek detail ONU 16 lagi (Config state berubah?)
c($fp,'show gpon onu detail-info gpon-onu_1/1/1:16');
fclose($fp);