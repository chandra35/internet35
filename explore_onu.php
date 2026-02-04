<?php
// Try correct ONU commands
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
            if (strpos($data, '--More--') !== false) {
                fwrite($fp, " ");
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

// Check for proper syntax
$output[] = "=== show onu info ? ===";
$output[] = cmd($fp, "show onu info ?", 3);

$output[] = "\n=== show onu config ? ===";
$output[] = cmd($fp, "show onu config ?", 3);

$output[] = "\n=== show epon 0/1 ? ===";
$output[] = cmd($fp, "show epon 0/1 ?", 3);

$output[] = "\n=== show onu types ===";
$output[] = cmd($fp, "show onu types", 3);

$output[] = "\n=== show onu info all ===";
$output[] = cmd($fp, "show onu info all", 5);

$output[] = "\n=== show epon 0/1 ===";
$output[] = cmd($fp, "show epon 0/1", 3);

fclose($fp);
file_put_contents('telnet_result.txt', implode("\n", $output));
echo "Done! Check telnet_result.txt\n";
