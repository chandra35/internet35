<?php
// Test sending specific commands to ONU 1/1/1:19 pon-onu-mng
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
    echo ">>> $cmd\n";
    fwrite($fp, "$cmd\r\n");
    usleep(500000);
    $out = readUntil($fp, 10);
    echo $out . "\n---\n";
    return $out;
}

waitFor($fp, ['Username:', 'login:']);
fwrite($fp, "zte\r\n");
waitFor($fp, ['Password:']);
fwrite($fp, "zte\r\n");
sleep(1);
readUntil($fp);

sendCmd($fp, "terminal length 0");
sendCmd($fp, "configure terminal");
sendCmd($fp, "pon-onu-mng gpon-onu_1/1/1:19");

// Test each missing command one by one
sendCmd($fp, "pppoe 1 nat enable user hisyam password 1234");
sendCmd($fp, "dhcp-ip ethuni eth_0/1 from-onu");
sendCmd($fp, "dhcp-ip ethuni eth_0/2 from-onu");
sendCmd($fp, "dhcp-ip ethuni eth_0/3 from-onu");
sendCmd($fp, "dhcp-ip ethuni eth_0/4 from-onu");
sendCmd($fp, "security-mgmt 998 state enable mode forward ingress-type lan protocol web https");
sendCmd($fp, "security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069");

sendCmd($fp, "exit");
sendCmd($fp, "exit");

fclose($fp);
echo "\nDone!\n";
