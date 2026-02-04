<?php
/**
 * Get OLT info via Telnet (faster than SNMP when network slow)
 */
$host = '172.16.16.4';

echo "=== Hioso OLT Info via Telnet ===\n\n";

$fp = @fsockopen($host, 23, $errno, $errstr, 5);
if (!$fp) { echo "Connect failed: $errstr\n"; exit(1); }
stream_set_timeout($fp, 5);

function readUntilX($fp, $pattern, $timeout = 3) {
    $data = '';
    $start = time();
    while (time() - $start < $timeout) {
        $char = @fread($fp, 1024);
        if ($char) $data .= $char;
        if (preg_match($pattern, $data)) break;
        usleep(30000);
    }
    return $data;
}

function cmdX($fp, $command, $wait = 0.5) {
    fwrite($fp, $command . "\r\n");
    usleep($wait * 1000000);
    $data = @fread($fp, 8192);
    while (strpos($data, '--More--') !== false || strpos($data, '--- Enter') !== false) {
        fwrite($fp, "\r\n");
        usleep(300000);
        $data .= @fread($fp, 8192);
    }
    return $data;
}

// Login
readUntilX($fp, '/Username:/');
fwrite($fp, "admin\r\n");
readUntilX($fp, '/Password:/');
fwrite($fp, "admin\r\n");
readUntilX($fp, '/EPON>/');
echo "Logged in\n";

// Enable
fwrite($fp, "enable\r\n");
usleep(300000);
@fread($fp, 1024);

// Get version info
echo "\n=== Version Info ===\n";
$version = cmdX($fp, "show version", 2);
echo $version;

// Get PON ports info
echo "\n=== PON Ports ===\n";
for ($i = 1; $i <= 4; $i++) {
    $info = cmdX($fp, "show epon 0/$i optical-ddm", 1);
    if (strpos($info, 'Temperature') !== false) {
        echo "PON 0/$i:\n";
        if (preg_match('/Temperature\s*:\s*([\d.]+)/i', $info, $m)) echo "  Temp: {$m[1]}C\n";
        if (preg_match('/TxPower\s*:\s*([\d.-]+)/i', $info, $m)) echo "  TxPower: {$m[1]} dBm\n";
    }
}

// Check ONU count on each port
echo "\n=== ONU Count per Port ===\n";
for ($i = 1; $i <= 4; $i++) {
    $info = cmdX($fp, "show onu info epon 0/$i all", 1.5);
    $lines = explode("\n", $info);
    $count = 0;
    foreach ($lines as $line) {
        if (preg_match('/^\s*\d+\s+/', trim($line))) {
            $count++;
        }
    }
    echo "EPON 0/$i: $count ONUs\n";
}

fclose($fp);
echo "\nDone!\n";
exit;

// OLD CODE BELOW
?><?php
// Check more OIDs for ONU info

$ip = '172.16.16.3';
$community = 'private';

@snmp_set_quick_print(true);
@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

// Check onuListTable description (.6)
echo "=== onuListTable description (.6) ===\n";
$walk = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.9.1.6', 1000000, 3);
if ($walk) {
    $count = 0;
    foreach ($walk as $oid => $value) {
        echo "$oid = $value\n";
        $count++;
        if ($count >= 15) break;
    }
}

// Check distance from opmDiagInfoTable
echo "\n\n=== opmDiagInfoTable structure ===\n";
// .1 = ponId, .2 = onuId, .3-? = various diag info

// Try .3 to .10
for ($i = 1; $i <= 12; $i++) {
    $oid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.$i.1.1";
    $val = @snmpget($ip, $community, $oid, 1000000, 1);
    echo ".12.2.1.8.1.$i.1.1 = $val\n";
}

echo "\n\n=== Check onuAuthInfoRtt (.13) for distance ===\n";
$walk2 = @snmpwalkoid($ip, $community, '1.3.6.1.4.1.37950.1.1.5.12.1.12.1.13', 1000000, 3);
if ($walk2) {
    $count = 0;
    foreach ($walk2 as $oid => $value) {
        echo "$oid = $value\n";
        $count++;
        if ($count >= 10) break;
    }
}
