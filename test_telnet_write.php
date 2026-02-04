<?php
// Minimal test with output buffering
$output = [];
$host = '172.16.16.4';

$output[] = "Starting...";

$fp = @fsockopen($host, 23, $errno, $errstr, 10);
if (!$fp) {
    $output[] = "Connect failed: $errstr";
    file_put_contents('telnet_result.txt', implode("\n", $output));
    exit(1);
}

$output[] = "Connected!";
stream_set_timeout($fp, 10);

// Read banner
sleep(2);
$data = '';
$start = time();
while (time() - $start < 5) {
    $char = @fread($fp, 1024);
    if ($char) $data .= $char;
    if (strpos($data, 'Username:') !== false) break;
    usleep(50000);
}
$output[] = "Banner: " . trim(str_replace(["\r", "\n"], " ", $data));

// Username
fwrite($fp, "admin\r\n");
sleep(1);
$data = @fread($fp, 1024);
$output[] = "After user: " . trim(str_replace(["\r", "\n"], " ", $data));

// Password
fwrite($fp, "admin\r\n");
sleep(1);
$data = @fread($fp, 2048);
$output[] = "After pass: " . trim(str_replace(["\r", "\n"], " ", $data));

// Enable
fwrite($fp, "enable\r\n");
sleep(1);
$data = @fread($fp, 2048);
$output[] = "After enable: " . trim(str_replace(["\r", "\n"], " ", $data));

// Show ONU
fwrite($fp, "show onu info epon 0/1\r\n");
sleep(3);
$data = @fread($fp, 8192);
$output[] = "ONU info epon 0/1:";
$output[] = $data;

// Show ONU port 2
fwrite($fp, "show onu info epon 0/2\r\n");
sleep(3);
$data = @fread($fp, 8192);
$output[] = "ONU info epon 0/2:";
$output[] = $data;

fclose($fp);
$output[] = "Done!";

// Write to file
file_put_contents('telnet_result.txt', implode("\n", $output));
echo "Result written to telnet_result.txt\n";
