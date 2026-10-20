<?php
// Direct telnet to ZTE C320 at 136.1.1.100
$host = '136.1.1.100';
$port = 23;
$user = 'zte';
$pass = 'zte';
$timeout = 10;

function telnetConnect($host, $port, $user, $pass, $timeout) {
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        echo "Connection failed: $errstr ($errno)\n";
        return false;
    }
    stream_set_timeout($fp, $timeout);
    
    // Wait for login prompt
    $buf = '';
    $start = time();
    while (time() - $start < $timeout) {
        $chunk = @fread($fp, 4096);
        if ($chunk) $buf .= $chunk;
        if (stripos($buf, 'sername:') !== false || stripos($buf, 'ogin:') !== false) break;
        usleep(100000);
    }
    echo "LOGIN PROMPT: " . trim($buf) . "\n";
    
    // Send username
    fwrite($fp, $user . "\r\n");
    usleep(500000);
    
    $buf = '';
    $start = time();
    while (time() - $start < $timeout) {
        $chunk = @fread($fp, 4096);
        if ($chunk) $buf .= $chunk;
        if (stripos($buf, 'assword:') !== false) break;
        usleep(100000);
    }
    
    // Send password
    fwrite($fp, $pass . "\r\n");
    usleep(1000000);
    
    $buf = '';
    $start = time();
    while (time() - $start < $timeout) {
        $chunk = @fread($fp, 4096);
        if ($chunk) $buf .= $chunk;
        if (preg_match('/[#>]\s*$/', $buf)) break;
        usleep(100000);
    }
    echo "AFTER LOGIN: " . trim($buf) . "\n\n";
    
    // Disable paging
    fwrite($fp, "terminal length 0\r\n");
    usleep(500000);
    $buf = '';
    $start = time();
    while (time() - $start < 5) {
        $chunk = @fread($fp, 4096);
        if ($chunk) $buf .= $chunk;
        if (preg_match('/[#>]\s*$/', $buf)) break;
        usleep(100000);
    }
    
    return $fp;
}

function runCommand($fp, $cmd, $timeout = 15) {
    echo ">>> Running: $cmd\n";
    fwrite($fp, $cmd . "\r\n");
    usleep(500000);
    
    $buf = '';
    $start = time();
    while (time() - $start < $timeout) {
        $chunk = @fread($fp, 8192);
        if ($chunk) {
            $buf .= $chunk;
        }
        if (preg_match('/[\w)][#>]\s*$/', $buf)) break;
        usleep(200000);
    }
    return $buf;
}

$fp = telnetConnect($host, $port, $user, $pass, $timeout);
if (!$fp) exit(1);

// Test 1: show vlan
echo "=== SHOW VLAN ===\n";
echo runCommand($fp, 'show vlan') . "\n";

// Test 2: show vlan 111 (detail)
echo "\n=== SHOW VLAN 111 ===\n";
echo runCommand($fp, 'show vlan 111') . "\n";

// Test 3: show vlan 335
echo "\n=== SHOW VLAN 335 ===\n";
echo runCommand($fp, 'show vlan 335') . "\n";

// Test 4: show running-config section about VLANs
echo "\n=== SHOW RUNNING-CONFIG VLAN ===\n";
$out = runCommand($fp, 'show running-config | begin vlan', 20);
echo substr($out, 0, 8000) . "\n";

fclose($fp);
