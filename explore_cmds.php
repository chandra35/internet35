<?php
// Explore commands available
$output = [];
$host = '172.16.16.4';

$fp = @fsockopen($host, 23, $errno, $errstr, 10);
if (!$fp) { echo "Connect failed\n"; exit(1); }

stream_set_timeout($fp, 10);

function readUntil($fp, $pattern, $timeout = 5) {
    $data = '';
    $start = time();
    while (time() - $start < $timeout) {
        $char = @fread($fp, 1024);
        if ($char) $data .= $char;
        if (preg_match($pattern, $data)) break;
        usleep(50000);
    }
    return $data;
}

function cmd($fp, $command, $timeout = 3) {
    fwrite($fp, $command . "\r\n");
    sleep(1);
    $data = '';
    $start = time();
    while (time() - $start < $timeout) {
        $char = @fread($fp, 4096);
        if ($char) $data .= $char;
        usleep(100000);
    }
    return $data;
}

// Login
readUntil($fp, '/Username:/');
fwrite($fp, "admin\r\n");
readUntil($fp, '/Password:/');
fwrite($fp, "admin\r\n");
readUntil($fp, '/EPON>/');

// Enable
cmd($fp, "enable");
$output[] = "=== In EPON# mode ===\n";

// Check available commands
$output[] = "\n=== show ? ===";
$result = cmd($fp, "show ?", 5);
$output[] = $result;

$output[] = "\n=== show onu ? ===";
$result = cmd($fp, "show onu ?", 5);
$output[] = $result;

$output[] = "\n=== show epon ? ===";
$result = cmd($fp, "show epon ?", 5);
$output[] = $result;

$output[] = "\n=== show interface ? ===";
$result = cmd($fp, "show interface ?", 5);
$output[] = $result;

fclose($fp);
file_put_contents('telnet_result.txt', implode("\n", $output));
echo "Done!\n";
