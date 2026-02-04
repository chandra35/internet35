<?php
/**
 * Quick Hioso PON Command Test
 */

$host = '172.16.16.4';
$socket = @fsockopen($host, 23, $errno, $errstr, 10);
if (!$socket) die("Failed\n");
stream_set_timeout($socket, 10);

// Simple read function
function rd($s, $t = 3) {
    usleep($t * 1000000);
    $b = '';
    while ($d = @fread($s, 8192)) $b .= $d;
    return $b;
}

// Login
rd($socket, 2);
fwrite($socket, "admin\r\n");
rd($socket, 1);
fwrite($socket, "admin\r\n");
rd($socket, 1);
fwrite($socket, "enable\r\n");
$o = rd($socket, 1);
if (stripos($o, 'assword') !== false) {
    fwrite($socket, "admin\r\n");
    rd($socket, 1);
}

echo "=== Connected ===\n";

// Test specific commands for optical transceiver
$cmds = [
    'show pon ?',
    'show epon ?', 
    'show olt ?',
    'show nni ?',
];

foreach ($cmds as $cmd) {
    fwrite($socket, "$cmd\r\n");
    $out = rd($socket, 2);
    echo "\n--- $cmd ---\n$out\n";
}

// Try show pon with port number
fwrite($socket, "show pon 1\r\n");
echo "\n--- show pon 1 ---\n" . rd($socket, 2);

// Try show epon onu
fwrite($socket, "show epon onu ?\r\n");
echo "\n--- show epon onu ? ---\n" . rd($socket, 2);

// show nni (uplink)
fwrite($socket, "show nni\r\n");
echo "\n--- show nni ---\n" . rd($socket, 3);

fclose($socket);
