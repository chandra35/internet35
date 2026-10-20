<?php
// Continue session: try various post-login endpoints + fetch HTML pages to find WAN config method names
$onuIp = '172.16.18.56';
$user  = 'admin';
$pass  = '%0|F?H@f!berhO3e';
$cookieJar = '/tmp/fh_cj.txt';
@unlink($cookieJar);

function fh_aes_iv(): string { $iv=''; for($i=0;$i<16;$i++)$iv.=chr($i+111); return $iv; }
function fhencrypt(string $d): string { $iv=fh_aes_iv(); return strtoupper(bin2hex(openssl_encrypt($d,'aes-128-cbc',$iv,OPENSSL_RAW_DATA,$iv))); }

function curl_xhr(string $onuIp, string $method, string $http, array $params=[]): array {
    global $cookieJar;
    $params['ajaxmethod']=$method; $params['_']=mt_rand()/mt_getrandmax();
    $body=http_build_query($params); $url="http://$onuIp/cgi-bin/ajax";
    $ch=curl_init();
    $opts=[
        CURLOPT_URL=>$http==='GET'?"$url?$body":$url,
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_COOKIEJAR=>$cookieJar, CURLOPT_COOKIEFILE=>$cookieJar,
        CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>['Referer: http://'.$onuIp.'/html/login_inter.html','X-Requested-With: XMLHttpRequest'],
    ];
    if($http==='POST'){$opts[CURLOPT_POST]=true; $opts[CURLOPT_POSTFIELDS]=$body; $opts[CURLOPT_HTTPHEADER][]='Content-Type: application/x-www-form-urlencoded';}
    curl_setopt_array($ch,$opts);
    $r=curl_exec($ch); $i=curl_getinfo($ch); curl_close($ch);
    return ['http'=>$i['http_code']??0,'body'=>$r];
}
function curl_get_html(string $url): array {
    global $cookieJar;
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookieJar,CURLOPT_COOKIEFILE=>$cookieJar,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>['Referer: http://172.16.18.56/']]);
    $r=curl_exec($ch); $i=curl_getinfo($ch); curl_close($ch);
    return ['http'=>$i['http_code']??0,'body'=>$r];
}
function parseJ(string $b): ?array { $m='Content-type: application/json'; if(($p=strpos($b,$m))!==false)$b=substr($b,$p+strlen($m)); $j=json_decode(trim($b),true); return is_array($j)?$j:null; }

// 0. seed
file_get_contents("http://$onuIp/");
$ch=curl_init("http://$onuIp/"); curl_setopt_array($ch,[CURLOPT_COOKIEJAR=>$cookieJar,CURLOPT_RETURNTRANSFER=>true]); curl_exec($ch); curl_close($ch);

// 1. login
$r=curl_xhr($onuIp,'get_refresh_sessionid','GET'); $sid=parseJ($r['body'])['sessionid']??null;
curl_xhr($onuIp,'get_operator_test','GET');
$r=curl_xhr($onuIp,'do_login','POST',['username'=>$user,'loginpd'=>fhencrypt($pass),'port'=>0,'sessionid'=>$sid]);
$lj=parseJ($r['body']);
echo "Login: " . json_encode($lj) . "\n\n";

// 2. fetch root after login -> see what page it serves now
echo "=== GET / (post-login) ===\n";
$r=curl_get_html("http://$onuIp/");
echo "HTTP {$r['http']}, len=".strlen($r['body'])."\n";
echo "First 600:\n".substr($r['body'],0,600)."\n\n";

// 3. fetch top-level html files commonly seen for WAN
foreach (['frame.html','menu.html','status.html','wan.html'] as $page) {
    echo "=== GET /html/$page ===\n";
    $r = curl_get_html("http://$onuIp/html/$page");
    echo "HTTP {$r['http']}, len=".strlen($r['body'])."\n";
    if ($r['http']==200 && strlen($r['body'])>100) {
        file_put_contents("/tmp/fh_$page",$r['body']);
    }
}

// 4. List js files referenced in frame.html / menu.html if any
foreach (['frame.html','menu.html'] as $page) {
    if (file_exists("/tmp/fh_$page")) {
        $h=file_get_contents("/tmp/fh_$page");
        if (preg_match_all('/(?:src|href)="([^"]+\.(?:html|js))"/',$h,$m)) {
            echo "\n--- /html/$page references ---\n";
            foreach (array_unique($m[1]) as $u) echo "  $u\n";
        }
    }
}
