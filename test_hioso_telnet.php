<?php
/**
 * Test Hioso OLT via Telnet
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Test Hioso OLT via Telnet ===\n";
echo "Host: {$host}:{$port}\n";
echo "User: {$username}\n\n";

// Try to connect via fsockopen
echo "Connecting...\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 10);

if (!$socket) {
    echo "❌ Failed to connect: {$errstr} (#{$errno})\n";
    exit(1);
}

echo "✅ Connected!\n\n";

// Set socket options
stream_set_timeout($socket, 5);
stream_set_blocking($socket, true);

// Function to read until we get expected string or timeout
function readUntil($socket, $patterns, $timeout = 5) {
    $buffer = '';
    $start = time();
    
    while (true) {
        $char = fread($socket, 1);
        if ($char === false || $char === '') {
            if (time() - $start > $timeout) {
                break;
            }
            usleep(10000);
            continue;
        }
        $buffer .= $char;
        
        foreach ((array)$patterns as $pattern) {
            if (stripos($buffer, $pattern) !== false) {
                return $buffer;
            }
        }
        
        if (time() - $start > $timeout) {
            break;
        }
    }
    
    return $buffer;
}

// Function to send command
function sendCommand($socket, $cmd) {
    fwrite($socket, $cmd . "\r\n");
    usleep(100000); // Wait 100ms
}

// Read initial banner
echo "--- Initial Banner ---\n";
$banner = readUntil($socket, ['login:', 'Username:', 'user:', '>'], 10);
echo $banner . "\n";

// Send username
echo "Sending username...\n";
sendCommand($socket, $username);

// Wait for password prompt
$response = readUntil($socket, ['password:', 'Password:', 'assword'], 5);
echo $response . "\n";

// Send password
echo "Sending password...\n";
sendCommand($socket, $password);

// Wait for prompt (usually > or # or device name)
echo "--- Login Response ---\n";
$response = readUntil($socket, ['>', '#', 'OLT', 'EPON', 'failed', 'incorrect', 'denied'], 5);
echo $response . "\n";

// Check if login successful
if (stripos($response, 'failed') !== false || 
    stripos($response, 'incorrect') !== false || 
    stripos($response, 'denied') !== false) {
    echo "❌ Login failed!\n";
    fclose($socket);
    exit(1);
}

echo "✅ Login successful!\n\n";

// Try some commands
$commands = [
    'show version',
    'show system',
    'show pon',
    'show epon',
    'show gpon',
    'show interface',
    'show onu',
    'show epon onu',
    'display version',
    'display system',
    'display onu',
    '?',
    'help',
];

foreach ($commands as $cmd) {
    echo "--- Command: {$cmd} ---\n";
    sendCommand($socket, $cmd);
    $output = readUntil($socket, ['>', '#', '--More--'], 3);
    
    // Handle pagination
    while (stripos($output, '--More--') !== false) {
        fwrite($socket, " "); // Send space to continue
        $output .= readUntil($socket, ['>', '#', '--More--'], 2);
    }
    
    // Clean up output
    $output = str_replace("\r", "", $output);
    $lines = explode("\n", $output);
    
    // Show first 20 lines
    $count = 0;
    foreach ($lines as $line) {
        if (trim($line) !== '' && $count < 20) {
            echo $line . "\n";
            $count++;
        }
    }
    if (count($lines) > 20) {
        echo "... (" . (count($lines) - 20) . " more lines)\n";
    }
    echo "\n";
    
    // If we got actual output, note it
    if (strlen(trim($output)) > 10 && 
        stripos($output, 'invalid') === false &&
        stripos($output, 'unknown') === false &&
        stripos($output, 'error') === false) {
        echo "✅ Command returned output\n\n";
    }
}

// Close connection
sendCommand($socket, 'exit');
sendCommand($socket, 'quit');
fclose($socket);

echo "\n=== Test Complete ===\n";
