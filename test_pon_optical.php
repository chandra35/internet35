<?php
/**
 * Test Hioso PON Optical Power via Telnet
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';
$timeout = 10;

echo "Connecting to Hioso OLT: $host\n";

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
    while ($data = @fread($socket, 8192)) {
        $buffer .= $data;
        if (empty($data)) break;
    }
    return $buffer;
}

// Login
echo "Logging in...\n";
$output = readUntil($socket, ['Username:', 'login:', '>'], 5);
fwrite($socket, "$username\r\n");

$output = readUntil($socket, ['Password:', 'password:'], 5);
fwrite($socket, "$password\r\n");

$output = readUntil($socket, ['>', '#', '$'], 5);
echo "Login result: " . substr($output, -200) . "\n";

// Try enable mode
fwrite($socket, "enable\r\n");
usleep(500000);
$output = @fread($socket, 4096);
if (stripos($output, 'assword') !== false) {
    fwrite($socket, "$password\r\n");
    usleep(500000);
    @fread($socket, 4096);
}

echo "\n=== Testing PON Optical Power Commands ===\n";

$commands = [
    'show ?',
    'show optical ?',
    'show optical-module ?',
    'show pon ?',
    'show interface pon 0/1',
    'show interface epon 0/1',
    'show epon ?',
    'show transceiver ?',
    'show transceiver',
    'show sfp',
    'show sfp ?',
    'show port optical',
    'show port transceiver',
    'show power ?',
    'show pon 0/1',
    'show pon power',
    'show pon power 0/1',
    'show pon optical',
    'show pon interface 0/1 optical',
    'show epon interface 0/1 optical',
    'show fiber',
    'show fiber ?',
    'display optical-module',
    'display transceiver',
];

foreach ($commands as $cmd) {
    echo "\n--- $cmd ---\n";
    $output = sendCommand($socket, $cmd, 2);
    
    // Clean output
    $lines = explode("\n", $output);
    $relevant = false;
    $lineCount = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        if (stripos($line, 'unknown') !== false || stripos($line, 'invalid') !== false) {
            echo "  (command not recognized)\n";
            break;
        }
        if (stripos($line, 'power') !== false || 
            stripos($line, 'optical') !== false || 
            stripos($line, 'dbm') !== false ||
            stripos($line, 'tx') !== false ||
            stripos($line, 'rx') !== false ||
            stripos($line, 'transceiver') !== false ||
            stripos($line, 'sfp') !== false) {
            $relevant = true;
        }
        
        echo "  $line\n";
        $lineCount++;
        if ($lineCount > 30) {
            echo "  ... (truncated)\n";
            break;
        }
    }
}

fwrite($socket, "exit\r\n");
fwrite($socket, "quit\r\n");
fclose($socket);

echo "\n=== Done ===\n";
