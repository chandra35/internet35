<?php
// Check batch CLI commands for optical power and distance
$host = '136.1.1.100';
$fp = @fsockopen($host, 23, $errno, $errstr, 5);
if (!$fp) { die("Telnet failed: $errstr\n"); }
stream_set_timeout($fp, 5);

function telnetRead($fp, $wait = 500000) {
    usleep($wait);
    $out = '';
    while (($chunk = @fread($fp, 8192)) !== false && $chunk !== '') {
        $out .= $chunk;
        if (preg_match('/#\s*$/', $out)) break;
        usleep(100000);
    }
    return $out;
}

// Login
telnetRead($fp, 500000);
fwrite($fp, "zte\r\n"); telnetRead($fp);
fwrite($fp, "zte\r\n"); telnetRead($fp);
fwrite($fp, "enable\r\n"); telnetRead($fp);
echo "Logged in.\n";

// 1. Batch OLT-RX (all ONUs on port 1/1/1)
echo "\n=== show pon power olt-rx gpon-olt_1/1/1 ===\n";
fwrite($fp, "show pon power olt-rx gpon-olt_1/1/1\r\n");
usleep(2000000);
$out = '';
for ($i = 0; $i < 10; $i++) {
    $chunk = @fread($fp, 16384);
    $out .= $chunk;
    if (preg_match('/#\s*$/', $out)) break;
    // Check for --More-- prompt
    if (strpos($chunk, '--More--') !== false) {
        fwrite($fp, " "); // space to continue
    }
    usleep(500000);
}
echo $out . "\n";

// 2. Batch ONU-RX (all ONUs on port 1/1/1)  
echo "\n=== show pon power onu-rx gpon-olt_1/1/1 ===\n";
fwrite($fp, "show pon power onu-rx gpon-olt_1/1/1\r\n");
usleep(2000000);
$out = '';
for ($i = 0; $i < 10; $i++) {
    $chunk = @fread($fp, 16384);
    $out .= $chunk;
    if (preg_match('/#\s*$/', $out)) break;
    if (strpos($chunk, '--More--') !== false) {
        fwrite($fp, " ");
    }
    usleep(500000);
}
echo $out . "\n";

// 3. ONU state with distance
echo "\n=== show gpon onu state gpon-olt_1/1/1 ===\n";
fwrite($fp, "show gpon onu state gpon-olt_1/1/1\r\n");
usleep(2000000);
$out = '';
for ($i = 0; $i < 10; $i++) {
    $chunk = @fread($fp, 16384);
    $out .= $chunk;
    if (preg_match('/#\s*$/', $out)) break;
    if (strpos($chunk, '--More--') !== false) {
        fwrite($fp, " ");
    }
    usleep(500000);
}
echo $out . "\n";

fclose($fp);
