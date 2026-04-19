<?php
// Direct telnet compare ONU 19 vs 20 — write to file to avoid plink truncation

$host = '136.1.1.100';
$port = 23;
$user = 'zte';
$pass = 'zte';
$outFile = '/tmp/onu_compare.txt';

function telnetCmd($sock, string $cmd, float $wait = 2.0): string {
    fwrite($sock, $cmd . "\r\n");
    usleep((int)($wait * 1000000));
    $out = '';
    while ($chunk = fread($sock, 8192)) {
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

$out = '';
$out .= "=== ONU 19 gpon-onu running-config ===\n";
$out .= telnetCmd($sock, "show running-config interface gpon-onu_1/1/1:19", 2);

$out .= "\n\n=== ONU 20 gpon-onu running-config ===\n";
$out .= telnetCmd($sock, "show running-config interface gpon-onu_1/1/1:20", 2);

$out .= "\n\n=== ONU 19 pon-onu-mng show commands ===\n";
$out .= telnetCmd($sock, "configure terminal", 1);
$out .= telnetCmd($sock, "pon-onu-mng gpon-onu_1/1/1:19", 1);
$out .= telnetCmd($sock, "show pppoe", 2);
$out .= telnetCmd($sock, "show flow", 2);
$out .= telnetCmd($sock, "show ip-host", 2);
$out .= telnetCmd($sock, "show vlan-filter", 2);
$out .= telnetCmd($sock, "show gemport", 2);
$out .= telnetCmd($sock, "end", 1);

$out .= "\n\n=== ONU 20 pon-onu-mng show commands ===\n";
$out .= telnetCmd($sock, "configure terminal", 1);
$out .= telnetCmd($sock, "pon-onu-mng gpon-onu_1/1/1:20", 1);
$out .= telnetCmd($sock, "show pppoe", 2);
$out .= telnetCmd($sock, "show flow", 2);
$out .= telnetCmd($sock, "show ip-host", 2);
$out .= telnetCmd($sock, "show vlan-filter", 2);
$out .= telnetCmd($sock, "show gemport", 2);
$out .= telnetCmd($sock, "end", 1);

fclose($sock);

file_put_contents($outFile, $out);
echo "Output written to $outFile (" . strlen($out) . " bytes)\n";
