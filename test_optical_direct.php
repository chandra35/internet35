<?php
// Direct telnet test - no SNMP

$host = '172.16.16.4';
$username = 'admin';
$password = 'admin';

echo "Direct Telnet Optical Test\n";
echo "==========================\n\n";

$sock = @fsockopen($host, 23, $errno, $errstr, 5);
if (!$sock) die("Connection failed: $errstr\n");

stream_set_timeout($sock, 10);

// Login
$buf = ''; $end = time() + 5;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (stripos($buf, 'login') !== false || stripos($buf, 'username') !== false) break; usleep(100000); }
fwrite($sock, "$username\r\n"); usleep(500000);
$buf = ''; $end = time() + 3;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (stripos($buf, 'password') !== false) break; usleep(100000); }
fwrite($sock, "$password\r\n"); usleep(500000);
$buf = ''; $end = time() + 3;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (strpos($buf, '>') !== false) break; usleep(100000); }
fwrite($sock, "enable\r\n"); usleep(500000);
$buf = ''; $end = time() + 2;
while (time() < $end) { $c = @fread($sock, 1024); if ($c) $buf .= $c; if (strpos($buf, '#') !== false) break; usleep(100000); }

echo "Logged in!\n\n";

// Test ONUs
$testOnus = [
    [0, 1, 3],
    [0, 1, 4],
    [0, 2, 1],
];

foreach ($testOnus as $onu) {
    [$slot, $port, $onuId] = $onu;
    
    $cmd = "show onu optical-ddm epon {$slot}/{$port} {$onuId}";
    echo "Testing: $cmd\n";
    
    fwrite($sock, "$cmd\r\n");
    usleep(500000);
    
    $out = '';
    $end = time() + 5;
    while (time() < $end) {
        $c = @fread($sock, 4096);
        if ($c) {
            $out .= $c;
            if (preg_match('/EPON#\s*$/', $out)) break;
        }
        usleep(100000);
    }
    
    // Parse output
    $txPower = 'N/A';
    $rxPower = 'N/A';
    $temp = 'N/A';
    
    if (preg_match('/TxPower\s*:\s*(-?[\d.]+)/i', $out, $m)) $txPower = $m[1];
    if (preg_match('/RxPower\s*:\s*(-?[\d.]+)/i', $out, $m)) $rxPower = $m[1];
    if (preg_match('/Temperature\s*:\s*(-?[\d.]+)/i', $out, $m)) $temp = $m[1];
    
    echo "  ONU {$slot}/{$port}:{$onuId}: Tx={$txPower}dBm, Rx={$rxPower}dBm, Temp={$temp}C\n\n";
}

fwrite($sock, "exit\r\n");
fclose($sock);

echo "Done!\n";
