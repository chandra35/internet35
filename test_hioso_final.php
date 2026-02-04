<?php
/**
 * Hioso OLT - Final ONU Discovery
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Hioso OLT ONU Discovery ===\n\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) { echo "❌ Failed\n"; exit(1); }

stream_set_timeout($socket, 10);

function readAll($socket, $timeout = 5) {
    $buffer = '';
    $start = time();
    stream_set_blocking($socket, false);
    
    while (time() - $start < $timeout) {
        $data = @fread($socket, 4096);
        if ($data) {
            $buffer .= $data;
            // Check for prompts
            if (preg_match('/EPON[>#\(]/', $buffer)) {
                // Wait a bit more for complete output
                usleep(200000);
                $data = @fread($socket, 4096);
                if ($data) $buffer .= $data;
                break;
            }
        }
        usleep(50000);
    }
    
    stream_set_blocking($socket, true);
    return str_replace("\r", "", $buffer);
}

function send($socket, $cmd) {
    fwrite($socket, $cmd . "\r\n");
    usleep(500000);
}

function execCmd($socket, $cmd, $timeout = 5) {
    send($socket, $cmd);
    $output = readAll($socket, $timeout);
    
    // Handle --More--
    while (strpos($output, '--More--') !== false) {
        fwrite($socket, " ");
        usleep(300000);
        $output .= readAll($socket, 3);
    }
    
    return $output;
}

// Login
echo "Logging in...\n";
readAll($socket, 5);
send($socket, $username);
readAll($socket, 3);
send($socket, $password);
readAll($socket, 3);

// Enable
send($socket, 'enable');
readAll($socket, 2);

// Config
send($socket, 'config terminal');
readAll($socket, 2);

echo "✅ Connected\n\n";

// Try the correct format: show onu info epon 0/x
echo "=== ONU Info per Port ===\n";
for ($p = 1; $p <= 4; $p++) {
    echo "--- EPON 0/{$p} ---\n";
    
    // Format: show onu info epon 0/x
    $output = execCmd($socket, "show onu info epon 0/{$p}", 6);
    
    // Clean and display
    $lines = explode("\n", $output);
    $inData = false;
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip command echo and prompts
        if (strpos($line, 'show onu') === 0) continue;
        if (strpos($line, 'EPON') === 0) continue;
        if (empty($line)) continue;
        
        // Look for table headers or data
        if (strpos($line, 'ONU') !== false || strpos($line, 'Mac') !== false || 
            strpos($line, 'Status') !== false || strpos($line, '---') !== false ||
            preg_match('/^\d+/', $line)) {
            echo $line . "\n";
            $inData = true;
        } elseif ($inData) {
            echo $line . "\n";
        }
    }
    echo "\n";
}

// Try ONU optical DDM
echo "=== ONU Optical DDM ===\n";
for ($p = 1; $p <= 4; $p++) {
    $output = execCmd($socket, "show onu optical-ddm epon 0/{$p}", 6);
    
    $lines = explode("\n", $output);
    $hasData = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'show onu') === 0 || strpos($line, 'EPON') === 0 || empty($line)) continue;
        
        if (strpos($line, 'ONU') !== false || strpos($line, 'Rx') !== false || 
            strpos($line, 'Tx') !== false || strpos($line, 'dBm') !== false ||
            strpos($line, '---') !== false || preg_match('/^\d+/', $line)) {
            if (!$hasData) {
                echo "--- EPON 0/{$p} ---\n";
                $hasData = true;
            }
            echo $line . "\n";
        }
    }
}

// Try ONU config
echo "\n=== ONU Config ===\n";
for ($p = 1; $p <= 4; $p++) {
    $output = execCmd($socket, "show onu config epon 0/{$p}", 6);
    
    $lines = explode("\n", $output);
    $hasData = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'show onu') === 0 || strpos($line, 'EPON') === 0 || empty($line)) continue;
        
        if (strpos($line, 'ONU') !== false || strpos($line, 'MAC') !== false || 
            strpos($line, 'SN') !== false || strpos($line, 'profile') !== false ||
            strpos($line, '---') !== false || preg_match('/^\d+/', $line) ||
            strpos($line, 'vlan') !== false) {
            if (!$hasData) {
                echo "--- EPON 0/{$p} ---\n";
                $hasData = true;
            }
            echo $line . "\n";
        }
    }
}

// Show OLT optical
echo "\n=== OLT Optical DDM ===\n";
$output = execCmd($socket, "show olt optical-ddm", 6);
$lines = explode("\n", $output);
foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, 'show olt') === 0 || strpos($line, 'EPON') === 0 || empty($line)) continue;
    echo $line . "\n";
}

// Show running-config summary
echo "\n=== Running Config (Summary) ===\n";
$output = execCmd($socket, "show running-config", 10);
$lines = explode("\n", $output);
$shown = 0;
foreach ($lines as $line) {
    if ($shown >= 50) {
        echo "... (truncated)\n";
        break;
    }
    $line = trim($line);
    if (strpos($line, 'EPON') === 0) continue;
    if (!empty($line)) {
        echo $line . "\n";
        $shown++;
    }
}

// Close
send($socket, 'end');
send($socket, 'exit');
fclose($socket);

echo "\n=== Done ===\n";
