<?php
$ip = '136.1.1.100';
$user = 'zte';
$pass = 'zte';

echo "Connecting...\n";
$fp = @fsockopen($ip, 23, $errno, $errstr, 10);
if (!$fp) die("Failed: $errstr\n");
stream_set_timeout($fp, 15);

function readUntil($fp, $patterns, $timeout = 15) {
    $buf = '';
    $start = time();
    $patterns = (array)$patterns;
    while (time() - $start < $timeout) {
        $meta = stream_get_meta_data($fp);
        if ($meta['timed_out']) break;
        $c = @fgetc($fp);
        if ($c === false) { usleep(50000); continue; }
        $buf .= $c;
        foreach ($patterns as $p) {
            if (stripos($buf, $p) !== false) return $buf;
        }
    }
    return $buf;
}

// Wait for Username prompt
$buf = readUntil($fp, 'Username:');
echo "Got username prompt\n";

fwrite($fp, "$user\r\n");
$buf = readUntil($fp, 'Password:');
echo "Got password prompt\n";

fwrite($fp, "$pass\r\n");
sleep(2);
$buf = readUntil($fp, ['#', '>']);
echo "Logged in: " . trim(substr($buf, -100)) . "\n\n";

// Disable paging
fwrite($fp, "terminal length 0\r\n");
sleep(1);
$buf = readUntil($fp, ['#', '>']);

// show version
fwrite($fp, "show version\r\n");
sleep(2);
$out = readUntil($fp, ['#', '>']);
echo "=== show version ===\n" . trim($out) . "\n\n";

// show card
fwrite($fp, "show card\r\n");
sleep(2);
$out = readUntil($fp, ['#', '>']);
echo "=== show card ===\n" . trim($out) . "\n\n";

// show running-config | include snmp
fwrite($fp, "show running-config | include snmp\r\n");
sleep(3);
$out = readUntil($fp, ['#', '>']);
echo "=== show run | include snmp ===\n" . trim($out) . "\n\n";

// show snmp status
fwrite($fp, "show snmp status\r\n");
sleep(2);
$out = readUntil($fp, ['#', '>']);
echo "=== show snmp status ===\n" . trim($out) . "\n\n";

// conf t then try snmp-server ?
fwrite($fp, "configure terminal\r\n");
sleep(1);
$buf = readUntil($fp, ['#', '>']);

fwrite($fp, "snmp-server ?\r\n");
sleep(2);
$out = readUntil($fp, ['#', '>']);
echo "=== snmp-server ? ===\n" . trim($out) . "\n\n";

fwrite($fp, "exit\r\n");
sleep(1);
readUntil($fp, ['#', '>']);

fclose($fp);
echo "Done.\n";
