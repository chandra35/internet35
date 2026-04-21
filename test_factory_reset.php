<?php
$host = '136.1.1.100';
$fp = fsockopen($host, 23, $errno, $errstr, 10);
if (!$fp) die("Cannot connect\n");
stream_set_timeout($fp, 3);

function rd($fp, $pat, $t=5) {
    $b=''; $s=time();
    while(time()-$s<$t) { $c=fread($fp,4096); if($c){$b.=$c;if(preg_match($pat,$b))break;}else usleep(100000); }
    return $b;
}
function wr($fp,$cmd) { fwrite($fp,"$cmd\r\n"); usleep(300000); }

rd($fp,'/name:/i');
wr($fp,'zte'); rd($fp,'/word:/i');
wr($fp,'zte'); rd($fp,'/[#>]/');
wr($fp,'terminal length 0'); rd($fp,'/[#>]/');

// Test restore factory - send and wait for prompt
echo "=== RESTORE FACTORY TEST ===\n";
wr($fp,'configure terminal'); rd($fp,'/[#>]/');
wr($fp,'pon-onu-mng gpon-onu_1/1/1:21'); rd($fp,'/[#>]/');

wr($fp,'restore factory');
echo "SENT: restore factory\n";
$out = rd($fp,'/[#>\]:]/',5);
echo "RESPONSE: ".json_encode($out)."\n\n";

// Send yes
wr($fp,'yes');
echo "SENT: yes\n";
$out = rd($fp,'/[#>]/',5);
echo "AFTER YES: ".json_encode($out)."\n\n";

// Now send reboot
wr($fp,'reboot');
echo "SENT: reboot\n";
$out = rd($fp,'/[#>\]:]/',5);
echo "REBOOT RESPONSE: ".json_encode($out)."\n\n";

// Send yes
wr($fp,'yes');
echo "SENT: yes\n";
$out = rd($fp,'/[#>]/',5);
echo "AFTER YES: ".json_encode($out)."\n\n";

wr($fp,'exit'); rd($fp,'/[#>]/');
wr($fp,'exit'); rd($fp,'/[#>]/');
fclose($fp);
echo "=== DONE ===\n";
