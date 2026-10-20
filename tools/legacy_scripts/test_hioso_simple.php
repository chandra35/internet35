<?php
/**
 * Hioso OLT - Simple Test One Command
 */

error_reporting(0);

$host = '172.16.16.4';
$port = 23;
$username = 'admin';
$password = 'admin';

echo "Connecting to {$host}...\n";

$socket = @fsockopen($host, $port, $errno, $errstr, 15);
if (!$socket) {
    echo "Failed: {$errstr}\n";
    exit(1);
}

stream_set_timeout($socket, 15);
echo "Connected!\n";

function read($socket, $timeout = 5) {
    $buffer = '';
    $end = time() + $timeout;
    
    while (time() < $end) {
        $info = stream_get_meta_data($socket);
        if ($info['eof']) break;
        
        $data = @fgets($socket, 1024);
        if ($data !== false) {
            $buffer .= $data;
            // Check for prompt
            if (preg_match('/(EPON[>#]|Password:|Username:)/', $buffer)) {
                break;
            }
        }
        usleep(100000);
    }
    
    return $buffer;
}

function cmd($socket, $command) {
    fwrite($socket, $command . "\r\n");
    sleep(1);
    return read($socket, 5);
}

// Login
echo "\n--- Login ---\n";
$output = read($socket, 5);
echo $output;

cmd($socket, $username);
sleep(1);
$output = read($socket, 3);
echo $output;

cmd($socket, $password);
sleep(1);
$output = read($socket, 3);
echo $output;

echo "\n--- Enter Enable Mode ---\n";
$output = cmd($socket, 'enable');
echo $output;

echo "\n--- Enter Config Mode ---\n";
$output = cmd($socket, 'config terminal');
echo $output;

echo "\n--- Show ONU Info EPON 0/1 ---\n";
fwrite($socket, "show onu info epon 0/1\r\n");
sleep(3);

// Read longer for this command
$output = '';
$end = time() + 8;
while (time() < $end) {
    $data = @fread($socket, 4096);
    if ($data) {
        $output .= $data;
        // Handle paging
        if (strpos($data, '--More--') !== false) {
            fwrite($socket, " ");
            sleep(1);
        }
    }
    usleep(100000);
}

echo $output . "\n";

// Close
fwrite($socket, "exit\r\n");
fclose($socket);

echo "\n--- Done ---\n";
