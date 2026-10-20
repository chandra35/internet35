<?php
$onuIp = '172.16.18.56';
$cookieJar = '/tmp/fh_cj.txt';
@unlink($cookieJar);

function curl_req($url, $opts = []) {
    global $cookieJar;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    foreach ($opts as $k => $v) curl_setopt($ch, $k, $v);
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $hs = $info['header_size'] ?? 0;
    return ['hdr'=>substr($body,0,$hs),'body'=>substr($body,$hs),'http'=>$info['http_code'] ?? 0];
}

foreach (['login_inter.js','xhr.js','util_functions.js','aes.js'] as $js) {
    echo "=== /js/$js ===\n";
    $r = curl_req("http://$onuIp/js/$js");
    echo "HTTP {$r['http']}, len=" . strlen($r['body']) . "\n";
    file_put_contents("/tmp/fh_$js", $r['body']);
}

echo "\n=== login_inter.js (key parts: search 'login', 'aes', 'XHR', 'post') ===\n";
$src = file_get_contents('/tmp/fh_login_inter.js');
foreach (preg_split('/\r?\n/', $src) as $i => $line) {
    if (preg_match('/login|XHR\.|post|aes|encrypt|getRand|password|user|csrf|token/i', $line)) {
        echo sprintf("%4d: %s\n", $i+1, trim($line));
    }
}
