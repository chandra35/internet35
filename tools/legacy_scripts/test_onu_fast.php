<?php
// Fast test with correct syntax and quick timing
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
    $data = @fread($fp, 8192);
    
    // Handle paging
    while (strpos($data, '--More--') !== false || strpos($data, '--- Enter') !== false) {
        fwrite($fp, "\r\n");
        usleep(300000);
        $data .= @fread($fp, 8192);
    }
    
    return $data;
}

echo "Connecting...\n";

// Fast login
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
echo "Enabled!\n";

// Quick commands
echo "\n=== ONU Info Port 0/1 ===\n";
echo fastCmd($fp, "show onu info epon 0/1", 1.5);

echo "\n=== ONU Info Port 0/2 ===\n";
echo fastCmd($fp, "show onu info epon 0/2", 1.5);

echo "\n=== ONU Info Port 0/3 ===\n";
echo fastCmd($fp, "show onu info epon 0/3", 1.5);

echo "\n=== ONU Info Port 0/4 ===\n";
echo fastCmd($fp, "show onu info epon 0/4", 1.5);

echo "\n=== OLT Optical DDM ===\n";
echo fastCmd($fp, "show epon 0/1 optical-ddm", 1.5);

fclose($fp);
echo "\nDone!\n";
