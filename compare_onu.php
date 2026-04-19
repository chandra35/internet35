<?php
// Read ONU 19 pon-onu-mng via ZTE telnet (text-only output, saved to readable file)

$host = '136.1.1.100';
$port = 23;
$user = 'zte';
$pass = 'zte';

function telnetRead($sock, float $wait = 2.0): string {
    usleep((int)($wait * 1000000));
    $out = '';
    while ($chunk = fread($sock, 8192)) {
        $out .= $chunk;
    }
    // Strip ANSI escape sequences and binary telnet negotiation bytes
    $out = preg_replace('/\x1b\[[0-9;]*[mGKHFABCDsuJr]/', '', $out);
    $out = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', $out);
    return $out;
}

function send($sock, string $cmd): void {
    fwrite($sock, $cmd . "\r\n");
}

$sock = fsockopen($host, $port, $errno, $errstr, 10);
if (!$sock) die("Connect failed: $errstr\n");
stream_set_blocking($sock, false);
sleep(2);
telnetRead($sock, 0.5); // banner

send($sock, $user);  telnetRead($sock, 1);
send($sock, $pass);  telnetRead($sock, 2);
send($sock, "terminal length 0"); telnetRead($sock, 1);

$out = "=== RUNNING CONFIG ONU 19 ===\n";
send($sock, "show running-config interface gpon-onu_1/1/1:19");
$out .= telnetRead($sock, 2);

$out .= "\n\n=== RUNNING CONFIG ONU 20 ===\n";
send($sock, "show running-config interface gpon-onu_1/1/1:20");
$out .= telnetRead($sock, 2);

// Enter config to access pon-onu-mng
send($sock, "configure terminal"); telnetRead($sock, 1);

$out .= "\n\n=== ONU 19 pon-onu-mng ===\n";
send($sock, "pon-onu-mng gpon-onu_1/1/1:19"); telnetRead($sock, 1);
send($sock, "show pppoe");  $out .= telnetRead($sock, 2);
send($sock, "show flow");   $out .= telnetRead($sock, 2);
send($sock, "show gemport"); $out .= telnetRead($sock, 2);
send($sock, "show ip-host"); $out .= telnetRead($sock, 2);
send($sock, "end"); telnetRead($sock, 1);

send($sock, "configure terminal"); telnetRead($sock, 1);

$out .= "\n\n=== ONU 20 pon-onu-mng ===\n";
send($sock, "pon-onu-mng gpon-onu_1/1/1:20"); telnetRead($sock, 1);
send($sock, "show pppoe");  $out .= telnetRead($sock, 2);
send($sock, "show flow");   $out .= telnetRead($sock, 2);
send($sock, "show gemport"); $out .= telnetRead($sock, 2);
send($sock, "show ip-host"); $out .= telnetRead($sock, 2);
send($sock, "end"); telnetRead($sock, 1);

fclose($sock);

file_put_contents('/tmp/onu_compare.txt', $out);
echo "Done. Bytes: " . strlen($out) . "\n";
