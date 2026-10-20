<?php
/**
 * Test Hioso OLT - Correct ONU Commands
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Hioso OLT - ONU Info ===\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) { echo "❌ Failed\n"; exit(1); }

stream_set_timeout($socket, 8);

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
    usleep(300000);
}

function execCmd($socket, $cmd, $timeout = 5) {
    send($socket, $cmd);
    $output = readUntil($socket, ['EPON>', 'EPON#', 'EPON(config)#', 'EPON(epon)#', '--More--'], $timeout);
    
    $attempts = 0;
    while (stripos($output, '--More--') !== false && $attempts < 30) {
        fwrite($socket, " ");
        usleep(100000);
        $output .= readUntil($socket, ['EPON>', 'EPON#', 'EPON(config)#', 'EPON(epon)#', '--More--'], 3);
        $attempts++;
    }
    
    return str_replace("\r", "", $output);
}

// Login
readUntil($socket, ['Username:', 'login:'], 10);
send($socket, $username);
readUntil($socket, ['Password:'], 5);
send($socket, $password);
readUntil($socket, ['EPON>'], 5);
echo "✅ Logged in\n";

// Enable mode
execCmd($socket, 'enable');
echo "✅ Enable mode\n";

// Config mode
execCmd($socket, 'config terminal');
echo "✅ Config mode\n\n";

// Show ONU info command help
echo "=== Show ONU Info Help ===\n";
$output = execCmd($socket, 'show onu info ?', 5);
echo $output . "\n";

// Show ONU types
echo "\n=== Show ONU Types ===\n";
$output = execCmd($socket, 'show onu types', 5);
echo $output . "\n";

// Try show onu config for each port
echo "\n=== Show ONU Config (all ports) ===\n";
for ($p = 1; $p <= 4; $p++) {
    echo "--- Port 0/{$p} ---\n";
    $output = execCmd($socket, "show onu config 0/{$p}", 5);
    
    // Parse output
    $lines = array_filter(explode("\n", $output), function($l) {
        return trim($l) !== '' && strpos($l, 'EPON') !== 0;
    });
    
    if (count($lines) > 2) {
        foreach (array_slice($lines, 0, 30) as $line) {
            echo $line . "\n";
        }
    } else {
        echo "No ONU on this port\n";
    }
    echo "\n";
}

// Try show onu info for each port
echo "\n=== Show ONU Info (all ports) ===\n";
for ($p = 1; $p <= 4; $p++) {
    echo "--- Port 0/{$p} ---\n";
    $output = execCmd($socket, "show onu info 0/{$p}", 5);
    
    $lines = array_filter(explode("\n", $output), function($l) {
        return trim($l) !== '' && strpos($l, 'EPON') !== 0 && strpos($l, 'show onu') !== 0;
    });
    
    if (count($lines) > 2) {
        foreach (array_slice($lines, 0, 30) as $line) {
            echo $line . "\n";
        }
    } else {
        echo "No ONU info on this port\n";
    }
    echo "\n";
}

// Try show onu optical-ddm
echo "\n=== Show ONU Optical DDM (all ports) ===\n";
for ($p = 1; $p <= 4; $p++) {
    echo "--- Port 0/{$p} ---\n";
    $output = execCmd($socket, "show onu optical-ddm 0/{$p}", 5);
    
    $lines = array_filter(explode("\n", $output), function($l) {
        return trim($l) !== '' && strpos($l, 'EPON') !== 0 && strpos($l, 'show onu') !== 0;
    });
    
    if (count($lines) > 2) {
        foreach (array_slice($lines, 0, 30) as $line) {
            echo $line . "\n";
        }
    } else {
        echo "No optical data on this port\n";
    }
    echo "\n";
}

// Show EPON interface status
echo "\n=== Show EPON Status ===\n";
$output = execCmd($socket, 'show epon 0/1', 5);
echo $output . "\n";

$output = execCmd($socket, 'show pon 0/1', 5);
echo $output . "\n";

// Check OLT info
echo "\n=== Show OLT Info ===\n";
$output = execCmd($socket, 'show olt ?', 5);
echo $output . "\n";

$output = execCmd($socket, 'show olt optical-ddm', 5);
echo $output . "\n";

// Show system
echo "\n=== Show System ===\n";
$output = execCmd($socket, 'show system', 5);
echo $output . "\n";

// Close
execCmd($socket, 'end');
send($socket, 'exit');
fclose($socket);

echo "\n=== Done ===\n";
