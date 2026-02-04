<?php
// Final correct syntax test
$host = '172.16.16.4';

$fp = @fsockopen($host, 23, $errno, $errstr, 10);
if (!$fp) { echo "Connect failed\n"; exit(1); }
stream_set_timeout($fp, 10);

function readUntil($fp, $pattern, $timeout = 3) {
    $data = '';
    $start = time();
    while (time() - $start < $timeout) {
        $char = @fread($fp, 2048);
        if ($char) $data .= $char;
        if (preg_match($pattern, $data)) break;
        usleep(30000);
    }
    return $data;
}

function fastCmd($fp, $command, $wait = 0.5) {
    fwrite($fp, $command . "\r\n");
    usleep($wait * 1000000);
    $data = @fread($fp, 16384);
    while (strpos($data, '--More--') !== false || strpos($data, '--- Enter') !== false) {
        fwrite($fp, "\r\n");
        usleep(500000);
        $data .= @fread($fp, 8192);
    }
    return $data;
}

echo "Connecting...\n";

// Login
readUntil($fp, '/Username:/');
fwrite($fp, "admin\r\n");
readUntil($fp, '/Password:/');
fwrite($fp, "admin\r\n");
readUntil($fp, '/EPON>/');
echo "Logged in!\n";

// Enable
fwrite($fp, "enable\r\n");
usleep(500000);
@fread($fp, 1024);

// Correct commands
echo "\n=== ONU Info All - Port 0/1 ===\n";
echo fastCmd($fp, "show onu info epon 0/1 all", 2);

echo "\n=== ONU Info All - Port 0/2 ===\n";
echo fastCmd($fp, "show onu info epon 0/2 all", 2);

echo "\n=== ONU Info All - Port 0/3 ===\n";
echo fastCmd($fp, "show onu info epon 0/3 all", 2);

echo "\n=== ONU Info All - Port 0/4 ===\n";
echo fastCmd($fp, "show onu info epon 0/4 all", 2);

echo "\n=== PON 1 Info ===\n";
echo fastCmd($fp, "show pon 1", 1.5);

echo "\n=== PON 2 Info ===\n";
echo fastCmd($fp, "show pon 2", 1.5);

echo "\n=== OLT MAC ===\n";
echo fastCmd($fp, "show olt mac", 1.5);

fclose($fp);
echo "\nDone!\n";
