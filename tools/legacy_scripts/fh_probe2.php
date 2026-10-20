<?php
// Probe Fiberhome XHR endpoints and try login
$onuIp = '172.16.18.56';
$user  = 'admin';
$pass  = '%0|F?H@f!berhO3e';

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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => ['Referer: http://172.16.18.56/', 'X-Requested-With: XMLHttpRequest'],
    ]);
    foreach ($opts as $k => $v) curl_setopt($ch, $k, $v);
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $headerSize = $info['header_size'] ?? 0;
    return [
        'hdr' => substr($body, 0, $headerSize),
        'body'=> substr($body, $headerSize),
        'http'=> $info['http_code'] ?? 0,
        'url' => $info['url'] ?? '',
    ];
}

echo "=== get / first to seed cookies ===\n";
$r = curl_req("http://$onuIp/");
echo "HTTP {$r['http']}\n";

echo "\n=== get /get_operator ===\n";
$r = curl_req("http://$onuIp/get_operator");
echo "HTTP {$r['http']}, body=" . substr($r['body'], 0, 300) . "\n";

echo "\n=== get /html/login_inter.html ===\n";
$r = curl_req("http://$onuIp/html/login_inter.html");
echo "HTTP {$r['http']}, body length=" . strlen($r['body']) . "\n";
echo "First 1500 chars:\n" . substr($r['body'], 0, 1500) . "\n";
file_put_contents('/tmp/fh_login_inter.html', $r['body']);
