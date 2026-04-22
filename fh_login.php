<?php
// Fiberhome HG6145F login probe
// All XHR -> ../cgi-bin/ajax with body: ajaxmethod=<method>&...&_=rand
// Login: get_refresh_sessionid (GET) then do_login (POST) with loginpd=fhencrypt(password)
// fhencrypt = AES-128-CBC, key=iv="opqrstuvwxyz{|}~", output = strtoupper(bin2hex(cipher))

$onuIp = '172.16.18.56';
$user  = 'admin';
$pass  = '%0|F?H@f!berhO3e';

$cookieJar = '/tmp/fh_cj.txt';
@unlink($cookieJar);

function fh_aes_iv(): string {
    $iv = '';
    for ($i = 0; $i < 16; $i++) $iv .= chr($i + 111);
    return $iv; // "opqrstuvwxyz{|}~"
}
function fhencrypt(string $data): string {
    $iv = fh_aes_iv();
    $cipher = openssl_encrypt($data, 'aes-128-cbc', $iv, OPENSSL_RAW_DATA, $iv);
    return strtoupper(bin2hex($cipher));
}

function curl_xhr(string $onuIp, string $method, string $http, array $params = []): array {
    global $cookieJar;
    $params['ajaxmethod'] = $method;
    $params['_'] = mt_rand() / mt_getrandmax();
    $body = http_build_query($params);
    $url  = "http://$onuIp/cgi-bin/ajax";
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $http === 'GET' ? "$url?$body" : $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => [
            'Referer: http://'.$onuIp.'/html/login_inter.html',
            'X-Requested-With: XMLHttpRequest',
        ],
    ];
    if ($http === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $body;
        $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['http' => $info['http_code'] ?? 0, 'body' => $resp];
}

function parse_fh_json(string $body): ?array {
    // Response prepended with "Content-type: application/json\n\n"
    $marker = 'Content-type: application/json';
    if (($p = strpos($body, $marker)) !== false) {
        $body = substr($body, $p + strlen($marker));
    }
    $body = trim($body);
    $j = json_decode($body, true);
    return is_array($j) ? $j : null;
}

// 0. seed cookies
echo "=== 0. seed cookies ===\n";
file_get_contents("http://$onuIp/");
$ch = curl_init("http://$onuIp/");
curl_setopt_array($ch, [CURLOPT_COOKIEJAR=>$cookieJar, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5]);
curl_exec($ch); curl_close($ch);

// 1. get_refresh_sessionid
echo "\n=== 1. GET get_refresh_sessionid ===\n";
$r = curl_xhr($onuIp, 'get_refresh_sessionid', 'GET');
echo "HTTP {$r['http']}\nRAW: " . substr($r['body'], 0, 300) . "\n";
$j = parse_fh_json($r['body']);
echo "JSON: " . print_r($j, true);
$sessionId = $j['sessionid'] ?? null;
if (!$sessionId) {
    echo "!! no sessionid, abort\n"; exit(1);
}

// 1b. get_operator_test (login_inter.js does this)
echo "\n=== 1b. GET get_operator_test ===\n";
$r = curl_xhr($onuIp, 'get_operator_test', 'GET');
echo "HTTP {$r['http']}, body=" . substr($r['body'], 0, 200) . "\n";

// 2. do_login
echo "\n=== 2. POST do_login ===\n";
$enc = fhencrypt($pass);
echo "fhencrypt('$pass') = $enc\n";
$r = curl_xhr($onuIp, 'do_login', 'POST', [
    'username' => $user,
    'loginpd'  => $enc,
    'port'     => 0,
    'sessionid'=> $sessionId,
]);
echo "HTTP {$r['http']}\nRAW: " . substr($r['body'], 0, 500) . "\n";
$j = parse_fh_json($r['body']);
echo "JSON: " . print_r($j, true);
echo "\nCookies after login:\n" . file_get_contents($cookieJar) . "\n";

// 3. fetch a post-login page to confirm session
echo "\n=== 3. test session: GET get_user_authority ===\n";
$r = curl_xhr($onuIp, 'get_user_authority', 'GET');
echo "HTTP {$r['http']}, body=" . substr($r['body'], 0, 300) . "\n";
