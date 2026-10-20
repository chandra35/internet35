<?php
/**
 * Hioso Telnet - Simple ONU Query
 */

$host = '172.16.16.4';
$username = 'admin';
$password = 'admin';

echo "Connecting to $host...\n";

$sock = @fsockopen($host, 23, $errno, $errstr, 5);
if (!$sock) die("Failed: $errstr\n");

stream_set_timeout($sock, 5);
stream_set_blocking($sock, true);

// Simple read function
function rd($sock, $wait = 2) {
    $buf = '';
    $end = time() + $wait;
    while (time() < $end) {
        $c = @fread($sock, 1024);
        if ($c) $buf .= $c;
        else usleep(100000);
        if (preg_match('/[#>]\s*$/', $buf)) break;
    }
    return $buf;
}

// Read initial
rd($sock, 3);

// Login
fwrite($sock, "$username\r\n");
usleep(500000);
rd($sock, 2);

fwrite($sock, "$password\r\n");
usleep(500000);
$r = rd($sock, 3);
echo "Login: " . (strpos($r, '>') !== false ? "OK\n" : "?\n");

// Enable
fwrite($sock, "enable\r\n");
usleep(500000);
$r = rd($sock, 2);
echo "Enable: " . (strpos($r, '#') !== false ? "OK\n" : "?\n");

// Query each port
for ($p = 1; $p <= 4; $p++) {
    echo "\n=== Port 0/$p ===\n";
    fwrite($sock, "show onu info epon 0/$p all\r\n");
    usleep(1000000);
    
    $out = '';
    $end = time() + 10;
    while (time() < $end) {
        $c = @fread($sock, 4096);
        if ($c) {
            $out .= $c;
            if (strpos($out, '--More--') !== false) {
                fwrite($sock, " ");
                $out = str_replace('--More--', '', $out);
            }
            if (preg_match('/EPON#\s*$/', $out)) break;
        }
        usleep(100000);
    }
    
    // Clean output
    $out = str_replace("\r", "", $out);
    $lines = array_filter(explode("\n", $out), 'trim');
    
    // Print (skip command echo)
    $skip = true;
    foreach ($lines as $l) {
        if ($skip && strpos($l, 'show onu') !== false) {
            $skip = false;
            continue;
        }
        if (!$skip && strpos($l, 'EPON#') === false) {
            echo "$l\n";
        }
    }
}

fwrite($sock, "exit\r\n");
fclose($sock);
echo "\nDone\n";
