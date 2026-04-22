<?php
function w($fp,$p,$t=10){$b='';$s=time();while(time()-$s<$t){$d=@fread($fp,4096);if($d)$b.=$d;foreach($p as $x){if(strpos($b,$x)!==false)return $b;}usleep(100000);}return $b;}
function rp($fp,$t=15){$b='';$d=time()+$t;while(time()<$d){$x=@fread($fp,4096);if($x)$b.=$x;if(preg_match('/[\w)][#>]\s*$/',$b))break;usleep(100000);}return $b;}
function c($fp,$cmd,$t=10){fwrite($fp,$cmd."\r\n");usleep(500000);$o=rp($fp,$t);echo "\n=== ".$cmd." ===\n".preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/','',$o);}
$fp=fsockopen('136.1.1.100',23,$e,$es,10);stream_set_timeout($fp,2);
w($fp,['Username:','login:']);fwrite($fp,"zte\r\n");
w($fp,['Password:']);fwrite($fp,"zte\r\n");
sleep(1);rp($fp);fwrite($fp,"terminal length 0\r\n");usleep(500000);rp($fp);

// dump full running-config, then grep manually
fwrite($fp,"show running-config\r\n");
sleep(1);
$all='';
$deadline=time()+60;
while(time()<$deadline) {
  $x=@fread($fp,65536);
  if($x){ $all.=$x; }
  if(preg_match('/ZXAN#\s*$/m', substr($all,-200))) break;
  usleep(100000);
}
file_put_contents('/tmp/full-config.txt', $all);
echo "SAVED, size=".strlen($all)."\n";

fclose($fp);
