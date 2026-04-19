<?php
// Get ONU 19 pon-onu-mng by dumping full running-config section 

$host = '136.1.1.100';
$port = 23;
$user = 'zte';
$pass = 'zte';

function telnetRead($sock, float $wait = 2.0): string {
    usleep((int)($wait * 1000000));
    $out = '';
    while ($chunk = fread($sock, 65536)) {
        $out .= $chunk;
    }
    // Strip ANSI escape and binary telnet bytes
    $out = preg_replace('/\xff[\xfb-\xfe]./', '', $out); // telnet options
    $out = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $out);
    $out = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', $out);
    return $out;
}

function send($sock, string $cmd): void { fwrite($sock, $cmd . "\r\n"); }

$sock = fsockopen($host, $port, $errno, $errstr, 10);
if (!$sock) die("Connect: $errstr\n");
stream_set_blocking($sock, false);
sleep(2); telnetRead($sock, 0.5);

send($sock, $user);  telnetRead($sock, 1);
send($sock, $pass);  telnetRead($sock, 2);
send($sock, "terminal length 0"); telnetRead($sock, 1);

// Dump full running-config, then extract ONU 19 and 20 sections
send($sock, "show running-config");
$full = '';
$start = time();
while (time() - $start < 15) {
    $chunk = fread($sock, 65536);
    if ($chunk) $full .= $chunk;
    else usleep(300000);
    if (str_contains($full, 'ZXAN#') && strlen($full) > 5000) {
        usleep(500000); // wait a bit more
        $chunk = fread($sock, 65536);
        if ($chunk) $full .= $chunk;
        break;
    }
}

// Clean binary
$full = preg_replace('/\xff[\xfb-\xfe]./', '', $full);
$full = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $full);
$full = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', $full);
$full = str_replace("\r\n", "\n", $full);

fclose($sock);

// Extract pon-onu-mng sections for ONU 19 and 20 using regex
preg_match('/pon-onu-mng gpon-onu_1\/1\/1:19\s*(.*?)(?=\n!|\npon-onu-mng|\nZXAN)/s', $full, $m19);
preg_match('/pon-onu-mng gpon-onu_1\/1\/1:20\s*(.*?)(?=\n!|\npon-onu-mng|\nZXAN)/s', $full, $m20);

$out = "=== ONU 19 pon-onu-mng ===\n";
$out .= isset($m19[0]) ? $m19[0] : "NOT FOUND\n";
$out .= "\n\n=== ONU 20 pon-onu-mng ===\n";
$out .= isset($m20[0]) ? $m20[0] : "NOT FOUND\n";
$out .= "\n\nTotal bytes received: " . strlen($full) . "\n";

file_put_contents('/tmp/onu_compare.txt', $out);
echo "Done. " . strlen($full) . " bytes scanned\n";
