<?php
// Final test with correct syntax
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
        if ($char) {
            $data .= $char;
            // Handle paging
            if (strpos($data, '--More--') !== false || strpos($data, '--- Enter Key') !== false) {
                fwrite($fp, "\r\n");
                sleep(1);
            }
        }
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

// Now with correct syntax
$output[] = "=== show onu info epon ? ===";
$output[] = cmd($fp, "show onu info epon ?", 3);

$output[] = "\n=== show onu info epon 0/1 ===";
$output[] = cmd($fp, "show onu info epon 0/1", 5);

$output[] = "\n=== show onu info epon 0/2 ===";
$output[] = cmd($fp, "show onu info epon 0/2", 5);

$output[] = "\n=== show onu info epon 0/3 ===";
$output[] = cmd($fp, "show onu info epon 0/3", 5);

$output[] = "\n=== show onu info epon 0/4 ===";
$output[] = cmd($fp, "show onu info epon 0/4", 5);

$output[] = "\n=== show epon 0/1 optical-ddm ===";
$output[] = cmd($fp, "show epon 0/1 optical-ddm", 3);

fclose($fp);
file_put_contents('telnet_result.txt', implode("\n", $output));
echo "Done!\n";
