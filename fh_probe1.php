<?php
// Probe Fiberhome HG6145F WebUI: login, fetch index, find WAN/PPPoE form
$onuIp = '172.16.18.56';
$user  = 'admin';
$pass  = '%0|F?H@f!berhO3e';

$cookieJar = tempnam(sys_get_temp_dir(), 'fh_cj_');

function curl_req($url, $opts = []) {
    global $cookieJar;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
    ]);
    foreach ($opts as $k => $v) curl_setopt($ch, $k, $v);
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err  = curl_error($ch);
    $headerSize = $info['header_size'] ?? 0;
    $hdr = substr($body, 0, $headerSize);
    $bod = substr($body, $headerSize);
    curl_close($ch);
    return ['hdr'=>$hdr,'body'=>$bod,'http'=>$info['http_code'] ?? 0,'url'=>$info['url'] ?? '','err'=>$err];
}

echo "=== STEP 1: GET / (look for login form, csrf token, login URL) ===\n";
$r = curl_req("http://$onuIp/");
echo "HTTP {$r['http']}  final-url={$r['url']}\n";
// Look for form action and any token field
if (preg_match('/<form[^>]*action="([^"]+)"[^>]*>(.*?)<\/form>/is', $r['body'], $m)) {
    echo "FORM action = {$m[1]}\n";
    if (preg_match_all('/<input[^>]+name="([^"]+)"[^>]*(?:value="([^"]*)")?/i', $m[2], $im, PREG_SET_ORDER)) {
        foreach ($im as $f) echo "  field: {$f[1]} = " . ($f[2] ?? '') . "\n";
    }
}
// title and obvious markers
if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $r['body'], $m)) echo "TITLE: " . trim($m[1]) . "\n";
echo "Body length: " . strlen($r['body']) . " bytes\n";
echo "First 800 chars:\n" . substr($r['body'], 0, 800) . "\n";

// Save full body for inspection
file_put_contents('/tmp/fh_login_page.html', $r['body']);
echo "\nSaved login page to /tmp/fh_login_page.html\n";
echo "Cookie jar: $cookieJar\n";
