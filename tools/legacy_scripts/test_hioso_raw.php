<?php
/**
 * Hioso OLT - Raw Output Check
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) { echo "❌ Failed\n"; exit(1); }

stream_set_timeout($socket, 10);

function readAll($socket, $timeout = 3) {
    $buffer = '';
    $start = time();
    stream_set_blocking($socket, false);
    
    while (time() - $start < $timeout) {
        $data = @fread($socket, 4096);
        if ($data) $buffer .= $data;
        usleep(50000);
    }
    
    stream_set_blocking($socket, true);
    return $buffer;
}

function send($socket, $cmd) {
    fwrite($socket, $cmd . "\r\n");
    usleep(500000);
}

// Login
readAll($socket, 3);
send($socket, $username);
readAll($socket, 2);
send($socket, $password);
readAll($socket, 2);

send($socket, 'enable');
readAll($socket, 2);

send($socket, 'config terminal');
readAll($socket, 2);

echo "=== RAW OUTPUT TEST ===\n\n";

// Test exact command and show raw output
$commands = [
    'show onu info epon 0/1',
    'show onu config epon 0/1',
    'show onu optical-ddm epon 0/1',
    'show olt optical-ddm',
    'show epon 0/1',
    'show pon 0/1',
];

foreach ($commands as $cmd) {
    echo "=== Command: {$cmd} ===\n";
    send($socket, $cmd);
    $output = readAll($socket, 4);
    
    // Handle --More--
    while (strpos($output, '--More--') !== false) {
        fwrite($socket, " ");
        usleep(300000);
        $output .= readAll($socket, 2);
    }
    
    // Show raw output
    echo "--- RAW ---\n";
    echo $output;
    echo "\n--- END ---\n\n";
}

// Close
send($socket, 'end');
send($socket, 'exit');
fclose($socket);
