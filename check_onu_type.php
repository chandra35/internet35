<?php
// Check ONU type for 1/1/1:19 (ours) vs 1/1/4:2 (SmartOLT)
$fp = @fsockopen('136.1.1.100', 23, $errno, $errstr, 5);
if (!$fp) die("Telnet failed: $errstr\n");
stream_set_timeout($fp, 2);

function readUntil($fp, $timeout = 10) {
    $buf = '';
    $deadline = time() + $timeout;
    while (time() < $deadline) {
        $c = @fread($fp, 4096);
        if ($c === false || $c === '') { usleep(100000); continue; }
        $buf .= $c;
        if (preg_match('/[\w)][#>]\s*$/', $buf) || preg_match('/\]\s*:\s*$/', $buf)) break;
    }
    return $buf;
}

function waitFor($fp, $patterns, $timeout = 10) {
    $buf = '';
    $deadline = time() + $timeout;
    while (time() < $deadline) {
        $c = @fread($fp, 4096);
        if ($c === false || $c === '') { usleep(100000); continue; }
        $buf .= $c;
        foreach ($patterns as $p) {
            if (stripos($buf, $p) !== false) return $buf;
        }
    }
    return $buf;
}

function sendCmd($fp, $cmd) {
    fwrite($fp, "$cmd\r\n");
    usleep(500000);
    return readUntil($fp, 10);
}

waitFor($fp, ['Username:', 'login:']);
fwrite($fp, "zte\r\n");
waitFor($fp, ['Password:']);
fwrite($fp, "zte\r\n");
sleep(1);
readUntil($fp);

sendCmd($fp, "terminal length 0");

// Show ONU type for both
echo "=== ONU 1/1/1:19 (OURS) ===\n";
$out = sendCmd($fp, "show gpon onu detail-info gpon-onu_1/1/1:19");
echo $out . "\n\n";

echo "=== ONU 1/1/4:2 (SMARTOLT) ===\n";
$out = sendCmd($fp, "show gpon onu detail-info gpon-onu_1/1/4:2");
echo $out . "\n\n";

// Also check onu type in running-config  
echo "=== RUNNING-CONFIG OLT PORT 1/1/1 (show onu type) ===\n";
$out = sendCmd($fp, "show running-config interface gpon-olt_1/1/1");
echo $out . "\n\n";

echo "=== RUNNING-CONFIG OLT PORT 1/1/4 (show onu type) ===\n";
$out = sendCmd($fp, "show running-config interface gpon-olt_1/1/4");
echo $out . "\n\n";

fclose($fp);
