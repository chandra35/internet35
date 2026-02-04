<?php
/**
 * Test Hioso PON Optical Power via Telnet - Save to file
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';
$timeout = 10;

$outputFile = __DIR__ . '/pon_optical_result.txt';
$log = fopen($outputFile, 'w');

function logWrite($log, $msg) {
    fwrite($log, $msg . "\n");
    echo $msg . "\n";
}

logWrite($log, "Connecting to Hioso OLT: $host");

$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
if (!$socket) {
    die("Connection failed: $errstr ($errno)\n");
}

stream_set_timeout($socket, $timeout);

function readUntil($socket, $patterns, $timeout = 10) {
    $start = time();
    $buffer = '';
    while (time() - $start < $timeout) {
        $data = @fread($socket, 4096);
        if ($data) {
            $buffer .= $data;
            foreach ($patterns as $pattern) {
                if (stripos($buffer, $pattern) !== false) {
                    return $buffer;
                }
            }
        }
        usleep(100000);
    }
    return $buffer;
}

function sendCommand($socket, $cmd, $wait = 2) {
    fwrite($socket, $cmd . "\r\n");
    usleep($wait * 1000000);
    $buffer = '';
    while (true) {
        $data = @fread($socket, 8192);
        if (empty($data)) break;
        $buffer .= $data;
    }
    return $buffer;
}

// Login
$output = readUntil($socket, ['Username:', 'login:', '>'], 5);
fwrite($socket, "$username\r\n");
$output = readUntil($socket, ['Password:', 'password:'], 5);
fwrite($socket, "$password\r\n");
$output = readUntil($socket, ['>', '#', '$'], 5);

// Enter enable mode
fwrite($socket, "enable\r\n");
usleep(500000);
$output = @fread($socket, 4096);
if (stripos($output, 'assword') !== false) {
    fwrite($socket, "$password\r\n");
    usleep(500000);
    @fread($socket, 4096);
}

logWrite($log, "Login OK - Testing commands");

// Test commands
$commands = [
    'show ?',
    'show pon ?',
    'show port',
    'show pon 0/1',
    'show pon 1',
    'show epon ?',
    'show transceiver',
    'show transceiver interface',
    'show optical-transceiver interface pon 0/1',
    'show optical-transceiver interface',
    'show fiber-transceiver',
    'show sfp',
];

foreach ($commands as $cmd) {
    logWrite($log, "\n=== $cmd ===");
    $output = sendCommand($socket, $cmd, 2);
    
    // Clean and output
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && $line !== $cmd) {
            logWrite($log, $line);
        }
    }
}

fwrite($socket, "exit\r\n");
fclose($socket);
fclose($log);

echo "\n=== Results saved to $outputFile ===\n";
