<?php
// Test wan_modify on existing WAN entry of Fiberhome ONU 16
$ip='172.16.18.56'; $user='admin'; $pass='%0|F?H@f!berhO3e';
$cj='/tmp/fh_cj.txt'; @unlink($cj);

function aesiv(){$iv=''; for($i=0;$i<16;$i++)$iv.=chr($i+111); return $iv;}
function fhe($d){$iv=aesiv(); return strtoupper(bin2hex(openssl_encrypt($d,'aes-128-cbc',$iv,OPENSSL_RAW_DATA,$iv)));}
function xhr($ip,$m,$h,$p=[]){global $cj; $p['ajaxmethod']=$m; $p['_']=mt_rand()/mt_getrandmax(); $b=http_build_query($p);
    $u="http://$ip/cgi-bin/ajax"; $ch=curl_init();
    $o=[CURLOPT_URL=>$h==='GET'?"$u?$b":$u,CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cj,CURLOPT_COOKIEFILE=>$cj,CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>['Referer: http://'.$ip.'/html/broadband_inter.html','X-Requested-With: XMLHttpRequest']];
    if($h==='POST'){$o[CURLOPT_POST]=true;$o[CURLOPT_POSTFIELDS]=$b;$o[CURLOPT_HTTPHEADER][]='Content-Type: application/x-www-form-urlencoded';}
    curl_setopt_array($ch,$o); $r=curl_exec($ch); $i=curl_getinfo($ch); curl_close($ch);
    return ['c'=>$i['http_code']??0,'b'=>$r];
}
function pj($b){$m='Content-type: application/json'; if(($p=strpos($b,$m))!==false)$b=substr($b,$p+strlen($m)); $j=json_decode(trim($b),true); return is_array($j)?$j:null;}

// seed cookies
file_get_contents("http://$ip/");
$ch=curl_init("http://$ip/"); curl_setopt_array($ch,[CURLOPT_COOKIEJAR=>$cj,CURLOPT_RETURNTRANSFER=>true]); curl_exec($ch); curl_close($ch);

// login
$sid = pj(xhr($ip,'get_refresh_sessionid','GET')['b'])['sessionid'] ?? '';
xhr($ip,'get_operator_test','GET');
$lr = pj(xhr($ip,'do_login','POST',['username'=>$user,'loginpd'=>fhe($pass),'port'=>0,'sessionid'=>$sid])['b']);
echo "login: ".json_encode($lr)."\n\n";

// re-grab fresh sessionid for subsequent POSTs
function getSid($ip){return pj(xhr($ip,'get_refresh_sessionid','GET')['b'])['sessionid']??'';}

echo "=== get_allwan_info ===\n";
$r = xhr($ip,'get_allwan_info','GET');
echo "HTTP {$r['c']}\nRAW (first 2000):\n".substr($r['b'],0,2000)."\n\n";
file_put_contents('/tmp/fh_wanlist.json',$r['b']);

echo "\n=== get_allwan_info_broadBand ===\n";
$r = xhr($ip,'get_allwan_info_broadBand','GET');
echo "HTTP {$r['c']}\nRAW (first 2000):\n".substr($r['b'],0,2000)."\n";
file_put_contents('/tmp/fh_wanlist_bb.json',$r['b']);
