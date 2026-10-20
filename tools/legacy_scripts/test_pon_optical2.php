<?php
/**
 * Test Hioso PON Optical Power via Telnet - Focused version
 */

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';
$timeout = 10;

echo "Connecting to Hioso OLT: $host\n";

$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
if (!$socket) {
    die("Connection failed: $errstr ($errno)\n");
}

stream_set_timeout($socket, $timeout);

function readUntil($socket, $patterns, $timeout = 10) {
    $start = time();
    $buffer = '';
    while (time() - $start < $timeout) {
        $data = @fread($socket, 4096);
        if ($data) {
            $buffer .= $data;
            foreach ($patterns as $pattern) {
                if (stripos($buffer, $pattern) !== false) {
                    return $buffer;
                }
            }
        }
        usleep(100000);
    }
    return $buffer;
}

function sendCommand($socket, $cmd, $wait = 2) {
    fwrite($socket, $cmd . "\r\n");
    usleep($wait * 1000000);
    $buffer = '';
    while (true) {
        $data = @fread($socket, 8192);
        if (empty($data)) break;
        $buffer .= $data;
    }
    return $buffer;
}

// Login
echo "Logging in...\n";
$output = readUntil($socket, ['Username:', 'login:', '>'], 5);
fwrite($socket, "$username\r\n");

$output = readUntil($socket, ['Password:', 'password:'], 5);
fwrite($socket, "$password\r\n");

$output = readUntil($socket, ['>', '#', '$'], 5);
echo "Login OK\n";

// Enter enable mode
fwrite($socket, "enable\r\n");
usleep(500000);
$output = @fread($socket, 4096);
if (stripos($output, 'assword') !== false) {
    fwrite($socket, "$password\r\n");
    usleep(500000);
    @fread($socket, 4096);
}

// Try 'show ?' to see all available show commands
echo "\n=== show ? ===\n";
$output = sendCommand($socket, 'show ?', 3);
// Print only lines with interesting keywords
$lines = explode("\n", $output);
foreach ($lines as $line) {
    $line = trim($line);
    if (strlen($line) > 2 && !preg_match('/^\s*$/', $line)) {
        echo "  $line\n";
    }
}

// Focus on pon-related show commands
echo "\n=== show pon ? ===\n";
$output = sendCommand($socket, 'show pon ?', 3);
echo $output . "\n";

// Try show port command
echo "\n=== show port ===\n";
$output = sendCommand($socket, 'show port', 3);
$lines = explode("\n", $output);
$count = 0;
foreach ($lines as $line) {
    echo trim($line) . "\n";
    if (++$count > 40) break;
}

// Try show pon 0/1
echo "\n=== show pon 0/1 ===\n";
$output = sendCommand($socket, 'show pon 0/1', 3);
echo $output . "\n";

// Try show pon 1
echo "\n=== show pon 1 ===\n";
$output = sendCommand($socket, 'show pon 1', 3);
echo $output . "\n";

// Try interface pon commands
echo "\n=== interface pon 0/1; show ? ===\n";
sendCommand($socket, 'interface pon 0/1', 1);
$output = sendCommand($socket, 'show ?', 3);
echo $output . "\n";

// Back to base
sendCommand($socket, 'exit', 1);

// Let's check 'show running-config' for pon interface info
echo "\n=== show running-config (pon section) ===\n";
$output = sendCommand($socket, 'show running-config', 5);
$lines = explode("\n", $output);
$inPon = false;
$count = 0;
foreach ($lines as $line) {
    if (stripos($line, 'pon') !== false || stripos($line, 'interface') !== false) {
        $inPon = true;
    }
    if ($inPon && $count < 50) {
        echo trim($line) . "\n";
        $count++;
    }
}

fwrite($socket, "exit\r\n");
fwrite($socket, "quit\r\n");
fclose($socket);

echo "\n=== Done ===\n";
