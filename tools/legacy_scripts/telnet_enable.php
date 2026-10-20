<?php
/**
 * Hioso Telnet - Enable mode and explore commands
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Hioso OLT Telnet - Privileged Mode ===\n";
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
    usleep(300000);
}

function readAll($socket, $timeout = 3) {
    $output = '';
    $start = time();
    while (time() - $start < $timeout) {
        $chunk = @fread($socket, 4096);
        if ($chunk) {
            $output .= $chunk;
            if (stripos($output, '--More--') !== false) {
                fwrite($socket, " ");
                $output = str_replace('--More--', '', $output);
            }
            if (preg_match('/[>#]\s*$/', trim($output))) {
                break;
            }
        }
        usleep(100000);
    }
    return trim(str_replace("\r", "", $output));
}

// Login
waitFor($socket, ['login:', 'Username:', 'user:'], 10);
send($socket, $username);
waitFor($socket, ['Password:', 'assword'], 5);
send($socket, $password);
$loginResp = waitFor($socket, ['>', '#'], 5);

echo "Login response: " . substr(trim($loginResp), -100) . "\n\n";

// Enter privileged mode
echo "=== Entering privileged mode ===\n";
send($socket, 'enable');
$enableResp = readAll($socket, 3);
echo "$enableResp\n";

// Check if need password for enable
if (stripos($enableResp, 'password') !== false) {
    echo "Enable needs password, trying admin...\n";
    send($socket, 'admin');
    echo readAll($socket, 3) . "\n";
}

// Get prompt to confirm mode
send($socket, '');
$prompt = readAll($socket, 2);
echo "Current prompt: $prompt\n\n";

// List available commands
echo "=== List commands ===\n";
send($socket, 'list');
$list = readAll($socket, 5);
echo "$list\n\n";

// Show sub-commands
echo "=== Show ? ===\n";
send($socket, 'show ?');
$showHelp = readAll($socket, 5);
echo "$showHelp\n\n";

// Try common ONU commands
$commands = [
    'show onu',
    'show onu all',
    'show onu info',
    'show epon',
    'show epon ?',
    'show interface',
    'show interface epon',
    'show running-config',
];

foreach ($commands as $cmd) {
    echo "=== $cmd ===\n";
    send($socket, $cmd);
    $out = readAll($socket, 5);
    
    // Show max 50 lines
    $lines = explode("\n", $out);
    foreach (array_slice($lines, 0, 50) as $l) {
        echo "$l\n";
    }
    if (count($lines) > 50) {
        echo "... +" . (count($lines) - 50) . " more\n";
    }
    echo "\n";
}

// Cleanup
send($socket, 'exit');
send($socket, 'exit');
fclose($socket);

echo "\nDone!\n";
