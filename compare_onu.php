<?php
// Direct telnet compare ONU 19 vs 20

$host = '136.1.1.100';
$port = 23;
$user = 'zte';
$pass = 'zte';

function telnetCmd($sock, string $cmd, float $wait = 1.5): string {
    fwrite($sock, $cmd . "\r\n");
    usleep((int)($wait * 1000000));
    $out = '';
    while ($chunk = fread($sock, 4096)) {
        $out .= $chunk;
    }
    return $out;
}

$sock = fsockopen($host, $port, $errno, $errstr, 10);
if (!$sock) die("Connect failed: $errstr\n");
stream_set_blocking($sock, false);
sleep(2);
fread($sock, 4096); // banner

// login
fwrite($sock, "$user\r\n"); sleep(1); fread($sock, 4096);
fwrite($sock, "$pass\r\n"); sleep(2); fread($sock, 4096);

// terminal length 0
telnetCmd($sock, "terminal length 0", 1);

echo "=== ONU 19 gpon-onu running-config ===\n";
echo telnetCmd($sock, "show running-config interface gpon-onu_1/1/1:19", 2);

echo "\n\n=== ONU 20 gpon-onu running-config ===\n";
echo telnetCmd($sock, "show running-config interface gpon-onu_1/1/1:20", 2);

echo "\n\n=== ONU 19 pon-onu-mng running-config ===\n";
echo telnetCmd($sock, "show running-config interface gpon-onu-mng_1/1/1:19", 4);

echo "\n\n=== ONU 20 pon-onu-mng running-config ===\n";
echo telnetCmd($sock, "show running-config interface gpon-onu-mng_1/1/1:20", 4);

fclose($sock);
