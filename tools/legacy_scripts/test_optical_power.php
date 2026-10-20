<?php
// Test Hioso Optical DDM Commands

$host = '172.16.16.4';
$username = 'admin';
$password = 'admin';

echo "=== Hioso Optical DDM Test ===\n\n";

$sock = @fsockopen($host, 23, $errno, $errstr, 5);
if (!$sock) die("Connection failed\n");

stream_set_timeout($sock, 30);

function rd($sock, $wait = 3) {
    $buf = '';
    $end = time() + $wait;
    while (time() < $end) {
        $c = @fread($sock, 1024);
        if ($c) $buf .= $c;
        else usleep(100000);
        if (preg_match('/EPON[#>]\s*$/', $buf)) break;
    }
    return $buf;
}

// Login
rd($sock, 5);
fwrite($sock, "$username\r\n"); usleep(500000);
rd($sock, 2);
fwrite($sock, "$password\r\n"); usleep(500000);
rd($sock, 3);
fwrite($sock, "enable\r\n"); usleep(500000);
rd($sock, 2);

echo "Logged in\n\n";

// Test: show epon 0/1 optical-ddm (per port)
echo "=== show epon 0/1 optical-ddm ===\n";
fwrite($sock, "show epon 0/1 optical-ddm\r\n");
usleep(2000000);

$out = '';
$end = time() + 10;
while (time() < $end) {
    $c = @fread($sock, 4096);
    if ($c) {
        $out .= $c;
        if (strpos($out, '--More--') !== false) {
            fwrite($sock, " ");
            $out = str_replace('--More--', '', $out);
        }
        if (preg_match('/EPON#\s*$/', $out)) break;
    }
    usleep(100000);
}

echo str_replace("\r", "", $out) . "\n\n";

// Test: show onu optical-ddm epon 0/1 1 (per ONU)
echo "=== show onu optical-ddm epon 0/1 1 ===\n";
fwrite($sock, "show onu optical-ddm epon 0/1 1\r\n");
usleep(2000000);

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

echo str_replace("\r", "", $out) . "\n";

fwrite($sock, "exit\r\n");
fclose($sock);

echo "\nDone!\n";
