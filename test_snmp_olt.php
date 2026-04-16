<?php
error_reporting(E_ALL);
echo 'PHP SNMP extension: ' . (function_exists('snmp2_get') ? 'YES' : 'NO') . PHP_EOL;
echo 'Testing SNMP to 136.1.1.100 with community=public...' . PHP_EOL;

$r = @snmp2_get('136.1.1.100', 'public', '1.3.6.1.2.1.1.1.0', 3000000, 0);
if ($r === false) {
    $err = error_get_last();
    echo 'SNMP public FAILED: ' . ($err['message'] ?? 'unknown') . PHP_EOL;
} else {
    echo 'SNMP public OK: ' . $r . PHP_EOL;
}

// Try community "zte"
$r2 = @snmp2_get('136.1.1.100', 'zte', '1.3.6.1.2.1.1.1.0', 3000000, 0);
if ($r2 === false) {
    $err = error_get_last();
    echo 'SNMP zte FAILED: ' . ($err['message'] ?? 'unknown') . PHP_EOL;
} else {
    echo 'SNMP zte OK: ' . $r2 . PHP_EOL;
}

// Try telnet
echo PHP_EOL . 'Testing Telnet to 136.1.1.100:23...' . PHP_EOL;
$fp = @fsockopen('136.1.1.100', 23, $errno, $errstr, 5);
if (!$fp) {
    echo 'Telnet FAILED: ' . $errstr . PHP_EOL;
} else {
    echo 'Telnet CONNECTED' . PHP_EOL;
    stream_set_timeout($fp, 5);
    // Read initial banner
    $banner = '';
    $start = time();
    while (time() - $start < 5) {
        $c = @fgetc($fp);
        if ($c === false) { usleep(100000); continue; }
        $banner .= $c;
        if (strpos($banner, 'Username:') !== false || strpos($banner, 'login:') !== false || strpos($banner, '>') !== false) {
            break;
        }
    }
    echo 'Banner: ' . trim(substr($banner, 0, 500)) . PHP_EOL;
    fclose($fp);
}

// Check Laravel log for recent identify errors
echo PHP_EOL . 'Last identify errors from log:' . PHP_EOL;
$logFile = '/www/wwwroot/internet35/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $relevant = [];
    foreach (array_slice($lines, -200) as $line) {
        if (stripos($line, 'identify') !== false || stripos($line, '136.1.1.100') !== false || stripos($line, 'snmp') !== false) {
            $relevant[] = trim($line);
        }
    }
    if (empty($relevant)) {
        echo '  No recent identify/SNMP errors found in last 200 lines' . PHP_EOL;
    } else {
        foreach (array_slice($relevant, -10) as $l) {
            echo '  ' . substr($l, 0, 300) . PHP_EOL;
        }
    }
} else {
    echo '  Log file not found' . PHP_EOL;
}
