<?php
// Minimal test - no autoload
$host = '172.16.16.4';

$fp = @fsockopen($host, 23, $errno, $errstr, 10);
if (!$fp) die("Connect failed\n");

stream_set_timeout($fp, 10);

// Read banner
sleep(2);
$data = '';
while ($char = fgets($fp, 1024)) {
    $data .= $char;
    if (strpos($data, 'Username:') !== false) break;
}
echo "Banner: " . trim($data) . "\n";

// Username
fwrite($fp, "admin\r\n");
sleep(1);
$data = '';
while ($char = fgets($fp, 1024)) {
    $data .= $char;
    if (strpos($data, 'Password:') !== false) break;
}
echo "After user: " . trim($data) . "\n";

// Password
fwrite($fp, "admin\r\n");
sleep(1);
$data = fread($fp, 4096);
echo "After pass: " . trim($data) . "\n";

// Enable
fwrite($fp, "enable\r\n");
sleep(1);
$data = fread($fp, 4096);
echo "After enable: " . trim($data) . "\n";

// Show ONU
fwrite($fp, "show onu info epon 0/1\r\n");
sleep(3);
$data = fread($fp, 8192);
echo "ONU info:\n" . $data . "\n";

fclose($fp);
