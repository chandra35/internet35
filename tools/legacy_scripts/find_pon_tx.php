<?php
/**
 * Find Hioso PON Transceiver TX Power command
 */

$host = '172.16.16.4';
$socket = @fsockopen($host, 23, $errno, $errstr, 10);
if (!$socket) die("Failed to connect\n");
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

echo "=== Connected to Hioso ===\n\n";

// Commands to find PON transceiver TX power
$cmds = [
    'show olt ?',
    'show olt',
    'show epon ?',
    'show epon olt',
    'show epon olt 0/1',
    'show epon optical-transceiver',
    'show epon optical-transceiver interface epon 0/1',
    'show interface epon 0/1',
    'show interface epon 0/1 transceiver',
    'show transceiver interface epon 0/1',
    'show epon interface 0/1',
    'show pon 0/1',
    'show pon 1',
    'show pon optical',
    'show optical-module transceiver epon 0/1',
    'show optical epon 0/1',
];

foreach ($cmds as $cmd) {
    fwrite($socket, "$cmd\r\n");
    $out = rd($socket, 2);
    
    // Check if has relevant info
    $hasInfo = preg_match('/tx|power|dbm|mw|temperature|voltage|bias/i', $out);
    $isUnknown = stripos($out, 'unknown') !== false || stripos($out, 'invalid') !== false;
    
    echo "--- $cmd ---\n";
    if ($isUnknown) {
        echo "  (unknown command)\n";
    } else {
        $lines = array_filter(explode("\n", $out), 'trim');
        $count = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && $line !== $cmd) {
                echo "  $line\n";
                if (++$count >= 20) { echo "  ...\n"; break; }
            }
        }
    }
    echo "\n";
}

// Try going into interface mode
echo "=== Trying interface epon 0/1 mode ===\n";
fwrite($socket, "interface epon 0/1\r\n");
$out = rd($socket, 1);
if (stripos($out, 'unknown') === false) {
    echo "Entered interface epon 0/1\n";
    
    fwrite($socket, "show ?\r\n");
    echo "--- show ? ---\n" . rd($socket, 2) . "\n";
    
    fwrite($socket, "show optical\r\n");
    echo "--- show optical ---\n" . rd($socket, 2) . "\n";
    
    fwrite($socket, "show transceiver\r\n");
    echo "--- show transceiver ---\n" . rd($socket, 2) . "\n";
    
    fwrite($socket, "exit\r\n");
    rd($socket, 1);
}

fwrite($socket, "quit\r\n");
fclose($socket);
echo "\nDone!\n";
