<?php
/**
 * Test Hioso OLT - Deep ONU Discovery
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Hioso OLT - ONU Discovery ===\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) { echo "❌ Failed\n"; exit(1); }

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
    $output = readUntil($socket, ['>', '#', '--More--', '(config', '(epon'], $timeout);
    
    $attempts = 0;
    while (stripos($output, '--More--') !== false && $attempts < 20) {
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
echo "✅ Logged in\n";

// Enable mode
execCmd($socket, 'enable');
echo "✅ Enable mode\n\n";

// Enter config mode
execCmd($socket, 'config terminal');
echo "✅ Config mode\n\n";

// Show ONU related commands
echo "=== Show ONU Commands ===\n";
$output = execCmd($socket, 'show onu ?', 5);
echo $output . "\n";

// Show EPON related commands  
echo "\n=== Show EPON Commands ===\n";
$output = execCmd($socket, 'show epon ?', 5);
echo $output . "\n";

// Show PON related commands
echo "\n=== Show PON Commands ===\n";
$output = execCmd($socket, 'show pon ?', 5);
echo $output . "\n";

// Try show epon-onu
echo "\n=== Show EPON-ONU ===\n";
$output = execCmd($socket, 'show epon-onu ?', 5);
echo $output . "\n";

// Try actual ONU listing
echo "\n=== Trying ONU List Commands ===\n";
$cmds = [
    'show epon-onu all',
    'show epon-onu 0/1',
    'show epon-onu 0/2',
    'show epon-onu 0/3',
    'show epon-onu 0/4',
    'show onu information',
    'show onu optical',
    'show epon optical',
    'show pon status',
    'show epon status',
];

foreach ($cmds as $cmd) {
    $output = execCmd($socket, $cmd, 4);
    if (stripos($output, 'Unknown') === false && 
        stripos($output, 'Invalid') === false &&
        strlen(trim($output)) > 30) {
        echo "--- {$cmd} ---\n";
        $lines = array_slice(array_filter(explode("\n", $output), 'trim'), 0, 40);
        echo implode("\n", $lines) . "\n\n";
    }
}

// Enter interface epon mode for each port
echo "\n=== Checking Each PON Port ===\n";
for ($port = 1; $port <= 4; $port++) {
    echo "--- Interface EPON 0/{$port} ---\n";
    
    // Enter interface
    $output = execCmd($socket, "interface epon 0/{$port}", 3);
    
    // Show interface commands
    $output = execCmd($socket, 'show ?', 5);
    if ($port == 1) {
        echo "Available show commands:\n";
        echo $output . "\n";
    }
    
    // Try ONU commands in interface mode
    $intCmds = [
        'show onu',
        'show onu all',
        'show onu information',
        'show onu optical',
        'show onu optical all',
        'show onu status',
        'show registered-onu',
        'show online-onu',
    ];
    
    foreach ($intCmds as $cmd) {
        $output = execCmd($socket, $cmd, 4);
        if (stripos($output, 'Unknown') === false && 
            stripos($output, 'Invalid') === false &&
            strlen(trim($output)) > 30) {
            echo "Port {$port} - {$cmd}:\n";
            $lines = array_slice(array_filter(explode("\n", $output), 'trim'), 0, 20);
            echo implode("\n", $lines) . "\n\n";
        }
    }
    
    // Exit interface
    execCmd($socket, 'exit');
}

// Show full running config for reference
echo "\n=== Full Running Config ===\n";
$output = execCmd($socket, 'show running-config', 10);
$lines = array_filter(explode("\n", $output), 'trim');
echo implode("\n", $lines) . "\n";

// Close
execCmd($socket, 'end');
send($socket, 'exit');
fclose($socket);

echo "\n=== Done ===\n";
