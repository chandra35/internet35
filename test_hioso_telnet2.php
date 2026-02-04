<?php
/**
 * Test Hioso OLT via Telnet - Explore Commands
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Hioso OLT Telnet - Command Explorer ===\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) {
    echo "❌ Failed: {$errstr}\n";
    exit(1);
}

stream_set_timeout($socket, 5);

function readUntil($socket, $patterns, $timeout = 5) {
    $buffer = '';
    $start = time();
    while (true) {
        $char = @fread($socket, 1);
        if ($char === false || $char === '') {
            if (time() - $start > $timeout) break;
            usleep(10000);
            continue;
        }
        $buffer .= $char;
        foreach ((array)$patterns as $p) {
            if (stripos($buffer, $p) !== false) return $buffer;
        }
        if (time() - $start > $timeout) break;
    }
    return $buffer;
}

function send($socket, $cmd) {
    fwrite($socket, $cmd . "\r\n");
    usleep(200000);
}

function execCmd($socket, $cmd, $timeout = 3) {
    send($socket, $cmd);
    $output = readUntil($socket, ['>', '#', '--More--'], $timeout);
    
    // Handle pagination
    $attempts = 0;
    while (stripos($output, '--More--') !== false && $attempts < 10) {
        fwrite($socket, " ");
        $output .= readUntil($socket, ['>', '#', '--More--'], 2);
        $attempts++;
    }
    
    return str_replace("\r", "", $output);
}

// Login
readUntil($socket, ['Username:', 'login:'], 10);
send($socket, $username);
readUntil($socket, ['Password:'], 5);
send($socket, $password);
readUntil($socket, ['>', '#'], 5);
echo "✅ Logged in\n\n";

// Enter enable mode
echo "=== Entering Enable Mode ===\n";
$output = execCmd($socket, 'enable');
echo $output . "\n";

// Check if we need password for enable
if (stripos($output, 'password') !== false) {
    echo "Enable requires password, trying common ones...\n";
    foreach (['', 'admin', 'enable', '123456'] as $pass) {
        send($socket, $pass);
        $output = readUntil($socket, ['>', '#', 'denied', 'incorrect'], 3);
        if (stripos($output, '#') !== false) {
            echo "✅ Enable mode activated with password: '{$pass}'\n";
            break;
        }
    }
}

// Get help/command list
echo "\n=== Available Commands (?) ===\n";
$output = execCmd($socket, '?', 5);
echo $output . "\n";

// Try more specific help
echo "\n=== Show Commands (show ?) ===\n";
$output = execCmd($socket, 'show ?', 5);
echo $output . "\n";

// Try config terminal
echo "\n=== Config Terminal ===\n";
$output = execCmd($socket, 'config terminal');
if (stripos($output, 'Unknown') === false) {
    echo $output . "\n";
    
    // Get config commands
    echo "\n=== Config Commands (?) ===\n";
    $output = execCmd($socket, '?', 5);
    echo $output . "\n";
    
    // Exit config
    execCmd($socket, 'exit');
}

// Try interface commands
echo "\n=== Interface/Port Commands ===\n";
$cmds = [
    'show running-config',
    'show startup-config', 
    'show port',
    'show slot',
    'show card',
    'show epon interface',
    'show epon olt',
    'show onu-list',
    'show onu all',
    'show onu information',
    'show epon onu-information',
    'show optical-module',
    'show optical-transceiver',
];

foreach ($cmds as $cmd) {
    echo "--- {$cmd} ---\n";
    $output = execCmd($socket, $cmd, 4);
    
    // Only show if not unknown command
    if (stripos($output, 'Unknown') === false && strlen(trim($output)) > 20) {
        // Show first 30 lines
        $lines = array_filter(explode("\n", $output), 'trim');
        $shown = 0;
        foreach ($lines as $line) {
            if ($shown < 30) {
                echo $line . "\n";
                $shown++;
            }
        }
        if (count($lines) > 30) {
            echo "... (" . (count($lines) - 30) . " more lines)\n";
        }
        echo "\n";
    }
}

// Try to find ONU commands specifically
echo "\n=== Finding ONU Commands ===\n";
$output = execCmd($socket, 'show onu ?', 5);
echo $output . "\n";

$output = execCmd($socket, 'show epon ?', 5);
echo $output . "\n";

// Try interface gpon/epon
echo "\n=== Interface EPON/GPON ===\n";
$output = execCmd($socket, 'interface epon 0/0', 3);
if (stripos($output, 'Unknown') === false) {
    echo "Entered interface epon 0/0\n";
    $output = execCmd($socket, '?', 5);
    echo $output . "\n";
    
    $output = execCmd($socket, 'show onu ?', 5);
    echo $output . "\n";
    
    execCmd($socket, 'exit');
}

// Close
send($socket, 'exit');
send($socket, 'quit');
fclose($socket);

echo "\n=== Done ===\n";
