<?php
/**
 * Hioso Telnet - Get ONU Info
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "=== Hioso OLT - Get ONU Info ===\n\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) die("Connection failed\n");

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
    usleep(500000); // Wait 500ms for response
}

function readResponse($socket, $timeout = 8) {
    $output = '';
    $start = time();
    while (time() - $start < $timeout) {
        $chunk = @fread($socket, 4096);
        if ($chunk) {
            $output .= $chunk;
            // Handle More prompt
            if (stripos($output, '--More--') !== false) {
                fwrite($socket, " ");
                $output = str_replace('--More--', '', $output);
            }
            // Check for command prompt return
            if (preg_match('/EPON[#>]\s*$/', $output)) {
                break;
            }
        }
        usleep(100000);
    }
    return trim(str_replace("\r", "", $output));
}

// Login
waitFor($socket, ['login:', 'Username:'], 10);
send($socket, $username);
waitFor($socket, ['Password:'], 5);
send($socket, $password);
waitFor($socket, ['>', '#'], 5);

// Enable
send($socket, 'enable');
readResponse($socket, 3);

echo "✅ Logged in to privileged mode\n\n";

// Check ONU info on each PON port (0/1 to 0/4)
for ($p = 1; $p <= 4; $p++) {
    $interface = "0/$p";
    echo "=== PON Port $interface - All ONUs ===\n";
    
    send($socket, "show onu info epon $interface all");
    $output = readResponse($socket, 10);
    echo "$output\n\n";
}

// Also try epon interface status
echo "=== EPON Port Status ===\n";
for ($p = 1; $p <= 4; $p++) {
    $interface = "0/$p";
    send($socket, "show epon $interface rate 1sec");
    echo readResponse($socket, 5) . "\n";
}

// Check optical DDM on first port 
echo "=== Optical DDM PON 0/1 ===\n";
send($socket, "show epon 0/1 optical-ddm");
echo readResponse($socket, 5) . "\n\n";

// Cleanup
send($socket, 'exit');
fclose($socket);

echo "\nDone!\n";
