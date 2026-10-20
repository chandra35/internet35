<?php
function w($fp,$p,$t=10){$b='';$s=time();while(time()-$s<$t){$d=@fread($fp,4096);if($d)$b.=$d;foreach($p as $x){if(strpos($b,$x)!==false)return $b;}usleep(100000);}return $b;}
function rp($fp,$t=15){$b='';$d=time()+$t;while(time()<$d){$x=@fread($fp,4096);if($x)$b.=$x;if(preg_match('/[\w)][#>]\s*$/',$b))break;usleep(100000);}return $b;}
function c($fp,$cmd,$t=10){fwrite($fp,$cmd."\r\n");usleep(500000);$o=rp($fp,$t);echo "\n=== ".$cmd." ===\n".preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$o);}
$fp=fsockopen('136.1.1.100',23,$e,$es,10);stream_set_timeout($fp,2);
w($fp,['Username:','login:']);fwrite($fp,"zte\r\n");
w($fp,['Password:']);fwrite($fp,"zte\r\n");
sleep(1);rp($fp);fwrite($fp,"terminal length 0\r\n");usleep(500000);rp($fp);

// Try multiple variations to find correct command
c($fp,"show running-config interface gpon-onu_1/1/1:16", 25);
c($fp,"show running-config | include 1/1/1:16", 25);
c($fp,"show running-config | include 1/1/1:17", 25);
// Try in pon-onu-mng mode with diff command names
c($fp,"configure terminal");
c($fp,"pon-onu-mng gpon-onu_1/1/1:16");
c($fp,"do show pon-onu-mng gpon-onu_1/1/1:16", 20);
c($fp,"show running-config", 25);
fclose($fp);
