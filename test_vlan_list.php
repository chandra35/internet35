<?php
// Direct telnet to ZTE C320 at 136.1.1.100 - Test VLAN list commands
$host = '136.1.1.100';
$port = 23;
$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$fp) { echo "FAIL: $errstr ($errno)\n"; exit(1); }
stream_set_timeout($fp, 10);

$buf = '';
$t = time();
while (time() - $t < 10) {
    $c = @fread($fp, 4096);
    if ($c) $buf .= $c;
    if (stripos($buf, 'sername:') !== false || stripos($buf, 'ogin:') !== false) break;
    usleep(100000);
}

fwrite($fp, "zte\r\n");
usleep(500000);
$buf = '';
$t = time();
while (time() - $t < 10) {
    $c = @fread($fp, 4096);
    if ($c) $buf .= $c;
    if (stripos($buf, 'assword:') !== false) break;
    usleep(100000);
}
fwrite($fp, "zte\r\n");
usleep(1000000);
$buf = '';
$t = time();
while (time() - $t < 10) {
    $c = @fread($fp, 4096);
    if ($c) $buf .= $c;
    if (preg_match('/[#>]\s*$/', $buf)) break;
    usleep(100000);
}

fwrite($fp, "terminal length 0\r\n");
usleep(500000);
$buf = '';
$t = time();
while (time() - $t < 5) {
    $c = @fread($fp, 4096);
    if ($c) $buf .= $c;
    if (preg_match('/[#>]\s*$/', $buf)) break;
    usleep(100000);
}

function runCmd($fp, $cmd, $wait = 3) {
    echo ">>> Running: $cmd\n";
    fwrite($fp, $cmd . "\r\n");
    usleep($wait * 1000000);
    $buf = '';
    $t = time();
    while (time() - $t < 15) {
        $c = @fread($fp, 8192);
        if ($c) $buf .= $c;
        if (preg_match('/[\w)][#>]\s*$/', $buf)) break;
        usleep(200000);
    }
    return $buf;
}

echo "=== TEST: show vlan all ===\n";
echo runCmd($fp, 'show vlan all') . "\n\n";

echo "=== TEST: show vlan summary ===\n";
echo runCmd($fp, 'show vlan summary') . "\n\n";

echo "=== TEST: show running-config include vlan ===\n";
echo runCmd($fp, 'show running-config | include ^vlan', 5) . "\n\n";

echo "=== TEST: show vlan 100 ===\n";
echo runCmd($fp, 'show vlan 100') . "\n\n";

echo "=== TEST: show vlan 338 ===\n";
echo runCmd($fp, 'show vlan 338') . "\n\n";

echo "=== TEST: show vlan 1035 ===\n";
echo runCmd($fp, 'show vlan 1035') . "\n\n";

echo "=== TEST: show dhcp snooping ===\n";
echo runCmd($fp, 'show ip dhcp snooping') . "\n\n";

fclose($fp);
