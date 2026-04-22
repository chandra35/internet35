<?php
// Modify existing PPPoE WAN entry on Fiberhome ONU 16: set VLAN=335
$ip='172.16.18.56'; $user='admin'; $pass='%0|F?H@f!berhO3e';
$cj='/tmp/fh_cj.txt'; @unlink($cj);

// PPPoE customer creds (re-use existing)
$pppUsername = 'tes@fiberhome';
$pppPassword = '1234';
$vlan        = 335;

function aesiv(){$iv=''; for($i=0;$i<16;$i++)$iv.=chr($i+111); return $iv;}
function fhe($d){$iv=aesiv(); return strtoupper(bin2hex(openssl_encrypt($d,'aes-128-cbc',$iv,OPENSSL_RAW_DATA,$iv)));}
function xhr($ip,$m,$h,$p=[]){global $cj; $p['ajaxmethod']=$m; $p['_']=mt_rand()/mt_getrandmax(); $b=http_build_query($p);
    $u="http://$ip/cgi-bin/ajax"; $ch=curl_init();
    $o=[CURLOPT_URL=>$h==='GET'?"$u?$b":$u,CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cj,CURLOPT_COOKIEFILE=>$cj,CURLOPT_TIMEOUT=>30,
        CURLOPT_HTTPHEADER=>['Referer: http://'.$ip.'/html/broadband_inter.html','X-Requested-With: XMLHttpRequest']];
    if($h==='POST'){$o[CURLOPT_POST]=true;$o[CURLOPT_POSTFIELDS]=$b;$o[CURLOPT_HTTPHEADER][]='Content-Type: application/x-www-form-urlencoded';}
    curl_setopt_array($ch,$o); $r=curl_exec($ch); $i=curl_getinfo($ch); curl_close($ch);
    return ['c'=>$i['http_code']??0,'b'=>$r];
}
function pj($b){$m='Content-type: application/json'; if(($p=strpos($b,$m))!==false)$b=substr($b,$p+strlen($m)); $j=json_decode(trim($b),true); return is_array($j)?$j:null;}

// 0. seed cookies
file_get_contents("http://$ip/");
$ch=curl_init("http://$ip/"); curl_setopt_array($ch,[CURLOPT_COOKIEJAR=>$cj,CURLOPT_RETURNTRANSFER=>true]); curl_exec($ch); curl_close($ch);

// 1. login
$sid=pj(xhr($ip,'get_refresh_sessionid','GET')['b'])['sessionid']??'';
xhr($ip,'get_operator_test','GET');
$lr=pj(xhr($ip,'do_login','POST',['username'=>$user,'loginpd'=>fhe($pass),'port'=>0,'sessionid'=>$sid])['b']);
echo "login: ".json_encode($lr)."\n";

// 2. wan_modify on iporppp=2 (PPPoE entry)
$post = [
    'wan_index'              => 1,
    'wan_session_index'      => 1,
    'wan_iporppp_old'        => 2,
    'wan_iporppp_new'        => 2,        // PPPoE
    'ConnectionType'         => 'PPPoE_Routed',
    'ServiceList'            => 'INTERNET',
    'IPMode'                 => 1,
    'mtu'                    => 1492,
    'VLANEnabled'            => 2,
    'vlanid'                 => $vlan,
    'p8021'                  => 0,
    'LanInterface'           => '',
    'AddressingType'         => 'PPPoE',
    'Username'               => $pppUsername,
    'WPd'                    => fhe($pppPassword),
    'ConnectionTrigger'      => 'AlwaysOn',
    'pppProxyEnable'         => 'NULL',
    'pppMAXUser'             => 'NULL',
    'pppToBridge'            => 'NULL',
    'NATEnabled'             => 1,
    'X_FH_AutoConnection'    => 1,
    'Dslite_Enable'          => 0,
];
// fresh sessionid for POST (XHR.post does this)
$post['sessionid'] = pj(xhr($ip,'get_refresh_sessionid','GET')['b'])['sessionid']??'';

echo "\n=== POST wan_modify (vlan=$vlan, user=$pppUsername) ===\n";
echo "Postdata:\n"; print_r($post);
$r = xhr($ip,'wan_modify','POST',$post);
echo "HTTP {$r['c']}\nRESP: ".$r['b']."\n";

// 3. re-fetch wan list to confirm
sleep(3);
echo "\n=== verify get_allwan_info ===\n";
$r = xhr($ip,'get_allwan_info','GET');
$j = pj($r['b']);
if ($j && isset($j['wan'])) {
    foreach ($j['wan'] as $w) {
        echo "- iporppp={$w['iporppp']} vlanid={$w['vlanid']} Name={$w['Name']} AddrType={$w['AddressingType']} IP={$w['ExternalIPAddress']} Status=".($w['ConnectionStatus']??'')."\n";
    }
} else {
    echo $r['b']."\n";
}
