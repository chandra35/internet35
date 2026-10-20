<?php
/**
 * Find Hioso PON Transceiver TX Power command - write to file
 */

$host = '172.16.16.4';
$outputFile = __DIR__ . '/hioso_pon_tx.txt';
$log = fopen($outputFile, 'w');

$socket = @fsockopen($host, 23, $errno, $errstr, 10);
if (!$socket) {
    fwrite($log, "Failed to connect\n");
    fclose($log);
    die("Failed\n");
}
stream_set_timeout($socket, 10);

function rd($s, $t = 2) {
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

fwrite($log, "=== Connected to Hioso ===\n\n");

// Commands to find PON transceiver TX power
$cmds = [
    'show olt',
    'show olt optical-transceiver',
    'show epon olt',
    'show epon olt optical',
    'show interface epon 0/1',
    'show interface epon 0/2',
    'show pon 1',
    'show pon 2',
];

foreach ($cmds as $cmd) {
    fwrite($socket, "$cmd\r\n");
    $out = rd($socket, 2);
    
    fwrite($log, "--- $cmd ---\n");
    fwrite($log, $out . "\n\n");
}

fwrite($socket, "quit\r\n");
fclose($socket);
fclose($log);

echo "Results written to: $outputFile\n";
echo file_get_contents($outputFile);
