<?php
// Discover post-login pages + ajax methods
$ip='172.16.18.56'; $user='admin'; $pass='%0|F?H@f!berhO3e';
$cj='/tmp/fh_cj.txt'; @unlink($cj);

function aesiv(){$iv=''; for($i=0;$i<16;$i++)$iv.=chr($i+111); return $iv;}
function fhe($d){$iv=aesiv(); return strtoupper(bin2hex(openssl_encrypt($d,'aes-128-cbc',$iv,OPENSSL_RAW_DATA,$iv)));}
function xhr($ip,$m,$h,$p=[]){global $cj; $p['ajaxmethod']=$m; $p['_']=mt_rand()/mt_getrandmax(); $b=http_build_query($p);
    $u="http://$ip/cgi-bin/ajax"; $ch=curl_init();
    $o=[CURLOPT_URL=>$h==='GET'?"$u?$b":$u,CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cj,CURLOPT_COOKIEFILE=>$cj,CURLOPT_TIMEOUT=>10,
        CURLOPT_HTTPHEADER=>['Referer: http://'.$ip.'/html/login_inter.html','X-Requested-With: XMLHttpRequest']];
    if($h==='POST'){$o[CURLOPT_POST]=true;$o[CURLOPT_POSTFIELDS]=$b;$o[CURLOPT_HTTPHEADER][]='Content-Type: application/x-www-form-urlencoded';}
    curl_setopt_array($ch,$o); $r=curl_exec($ch); $i=curl_getinfo($ch); curl_close($ch);
    return ['c'=>$i['http_code']??0,'b'=>$r];
}
function getH($url){global $cj; $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cj,CURLOPT_COOKIEFILE=>$cj,CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>['Referer: http://172.16.18.56/']]);
    $r=curl_exec($ch); $i=curl_getinfo($ch); curl_close($ch); return ['c'=>$i['http_code']??0,'b'=>$r];
}
function pj($b){$m='Content-type: application/json'; if(($p=strpos($b,$m))!==false)$b=substr($b,$p+strlen($m)); $j=json_decode(trim($b),true); return is_array($j)?$j:null;}

// seed + login
file_get_contents("http://$ip/");
$ch=curl_init("http://$ip/"); curl_setopt_array($ch,[CURLOPT_COOKIEJAR=>$cj,CURLOPT_RETURNTRANSFER=>true]); curl_exec($ch); curl_close($ch);
$sid=pj(xhr($ip,'get_refresh_sessionid','GET')['b'])['sessionid']??'';
xhr($ip,'get_operator_test','GET');
$lr=pj(xhr($ip,'do_login','POST',['username'=>$user,'loginpd'=>fhe($pass),'port'=>0,'sessionid'=>$sid])['b']);
echo "login: ".json_encode($lr)."\n\n";

// === try a directory listing of /html via known IDN_TELKOM operator names ===
// Try common Fiberhome operator-templated entry pages
$candidates = [
    'index.html','main.html','frame_inter.html','frame.html','main_inter.html',
    'ssmp_status.html','status_inter.html','status.html',
    'ssmp_status_idn_telkom.html','ssmp_status_idntelkom.html',
    'main_idn_telkom.html','frame_idn_telkom.html','user_modifypw_idn_telkom.html',
    'user_modifypw_omn_omantel.html', // referenced in JS
    // wan-related guesses
    'net_wan.html','wan_internet.html','net_wan_inter.html','net_wan_inter_idn_telkom.html',
    'wan_pppoe.html','net_dsl.html','net_wan_4line.html'
];
echo "=== try candidate /html/* ===\n";
foreach ($candidates as $c) {
    $r = getH("http://$ip/html/$c");
    $tag = $r['c']==200 ? 'OK ('.strlen($r['b']).')' : '['.$r['c'].']';
    echo str_pad($c,50)." $tag\n";
    if ($r['c']==200 && strlen($r['b'])>200) {
        file_put_contents("/tmp/fh_$c", $r['b']);
    }
}

// also try directory listing root js -> js folder might have many files
echo "\n=== /js/ directory listing? ===\n";
$r = getH("http://$ip/js/");
echo "HTTP {$r['c']}, len=".strlen($r['b'])."\n";
echo substr($r['b'],0,500)."\n";
