<?php
/**
 * Quick Hioso Telnet Test - Get ONU info
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Hioso OLT Telnet - ONU Discovery ===\n";
echo "Host: {$host}\n\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) {
    die("Connection failed: {$errstr}\n");
}

stream_set_timeout($socket, 30);

function waitFor($socket, $patterns, $timeout = 5) {
    $buf = '';
    $start = time();
    while (time() - $start < $timeout) {
        $c = @fread($socket, 1024);
        if ($c) {
            $buf .= $c;
            foreach ((array)$patterns as $p) {
                if (stripos($buf, $p) !== false) return $buf;
            }
        }
        usleep(50000);
    }
    return $buf;
}

function send($socket, $cmd) {
    fwrite($socket, $cmd . "\r\n");
    usleep(200000);
}

// Login
waitFor($socket, ['login:', 'Username:', 'user:'], 10);
send($socket, $username);
waitFor($socket, ['Password:', 'assword'], 5);
send($socket, $password);

$loginResp = waitFor($socket, ['>', '#', 'EPON'], 5);
if (stripos($loginResp, '>') === false && stripos($loginResp, '#') === false && stripos($loginResp, 'EPON') === false) {
    die("Login failed\n");
}

echo "✅ Logged in\n";
echo "Prompt detected: " . substr(trim($loginResp), -50) . "\n\n";

// Try to get help or command list
echo "=== Getting help ===\n";
send($socket, '?');
$help = waitFor($socket, ['>', '#'], 5);
echo "Help commands:\n" . substr($help, 0, 2000) . "\n\n";

// Try show commands
$tryCommands = [
    'show onu',
    'show epon onu',
    'show epon onu-info',
    'show onu all',
    'display onu all',
    'show epon onu-list',
    'show epon 0/1 onu',
    'show onu info epon 0/1',
];

foreach ($tryCommands as $cmd) {
    echo "=== Command: $cmd ===\n";
    send($socket, $cmd);
    
    $output = '';
    $start = time();
    
    // Keep reading until we get prompt back or timeout
    while (time() - $start < 5) {
        $chunk = @fread($socket, 4096);
        if ($chunk) {
            $output .= $chunk;
            
            // Handle More prompt
            if (stripos($output, '--More--') !== false) {
                fwrite($socket, " ");
                $output = str_replace('--More--', '', $output);
                usleep(100000);
            }
            
            // Check for command prompt return
            if (preg_match('/[>#]\s*$/', trim($output))) {
                break;
            }
        }
        usleep(50000);
    }
    
    // Clean and show
    $output = str_replace("\r", "", trim($output));
    $lines = explode("\n", $output);
    
    // Show max 30 lines
    foreach (array_slice($lines, 0, 30) as $line) {
        echo $line . "\n";
    }
    if (count($lines) > 30) {
        echo "... +" . (count($lines) - 30) . " more lines\n";
    }
    echo "\n";
    
    // If useful output found
    if (strlen($output) > 50 && 
        stripos($output, 'invalid') === false && 
        stripos($output, 'unknown') === false) {
        echo "✓ Got response\n\n";
    }
}

// Cleanup
send($socket, 'exit');
fclose($socket);

echo "\nDone!\n";
