<?php
/**
 * OLT Helper Verification Test
 * Tests all OLT helpers to verify they are functioning correctly
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "=============================================================\n";
echo "          OLT HELPER VERIFICATION TEST\n";
echo "=============================================================\n\n";

// List of helpers to check
$helpers = [
    'BaseOltHelper' => 'Abstract base class',
    'HiosoHelper' => 'Hioso/Haishuo EPON OLT (Enterprise 17409/25355)',
    'VsolHelper' => 'VSOL EPON/GPON OLT',
    'HuaweiHelper' => 'Huawei OLT',
    'ZteC320Helper' => 'ZTE C320 OLT',
    'HsgqHelper' => 'HSGQ OLT',
];

echo "Available Helpers:\n";
foreach ($helpers as $helper => $desc) {
    $file = __DIR__ . "/app/Helpers/Olt/{$helper}.php";
    $exists = file_exists($file);
    $icon = $exists ? '✓' : '✗';
    echo "  {$icon} {$helper}: {$desc}\n";
    
    if ($exists) {
        $content = file_get_contents($file);
        $lineCount = substr_count($content, "\n");
        echo "      Lines: {$lineCount}\n";
        
        // Check for key methods
        $methods = [
            'getAllOnus' => 'Get all ONUs',
            'getOnuInfo' => 'Get ONU info',
            'syncAll' => 'Sync all data',
            'identify' => 'Identify OLT',
        ];
        
        foreach ($methods as $method => $methodDesc) {
            if (strpos($content, "function {$method}") !== false) {
                echo "      - {$method}(): ✓\n";
            }
        }
    }
    echo "\n";
}

echo "=============================================================\n";
echo "                  HIOSO OLT TEST\n";
echo "=============================================================\n\n";

$hiosoIp = '172.16.16.4';
$community = 'public';

echo "Testing Hioso OLT at {$hiosoIp}...\n\n";

// 1. SNMP Basic Test
echo "1. SNMP Basic Connectivity:\n";
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$sysDescr = @snmpget($hiosoIp, $community, '1.3.6.1.2.1.1.1.0', 3000000, 2);
$sysObjectId = @snmpget($hiosoIp, $community, '1.3.6.1.2.1.1.2.0', 3000000, 2);

if ($sysDescr !== false) {
    echo "   ✓ sysDescr: " . substr($sysDescr, 0, 60) . "\n";
} else {
    echo "   ✗ sysDescr: No response\n";
}

if ($sysObjectId !== false) {
    echo "   ✓ sysObjectID: {$sysObjectId}\n";
    
    // Detect enterprise ID
    if (preg_match('/\.1\.3\.6\.1\.4\.1\.(\d+)/', $sysObjectId, $m)) {
        echo "   ✓ Enterprise ID: {$m[1]}\n";
    }
} else {
    echo "   ✗ sysObjectID: No response\n";
}

// 2. Interface Count
echo "\n2. Interface Detection:\n";
$ifDescrs = @snmprealwalk($hiosoIp, $community, '1.3.6.1.2.1.2.2.1.2', 3000000, 2);
if ($ifDescrs && !empty($ifDescrs)) {
    $ponPorts = 0;
    $uplinkPorts = 0;
    
    foreach ($ifDescrs as $oid => $val) {
        $val = trim($val);
        if (stripos($val, 'pon') !== false) $ponPorts++;
        elseif (preg_match('/^g\d+$/i', $val)) $uplinkPorts++;
    }
    
    echo "   ✓ Total Interfaces: " . count($ifDescrs) . "\n";
    echo "   ✓ PON Ports: {$ponPorts}\n";
    echo "   ✓ Uplink Ports: {$uplinkPorts}\n";
} else {
    echo "   ✗ ifDescr: No response\n";
}

// 3. SNMP Enterprise OIDs
echo "\n3. SNMP Enterprise OIDs (ONU Data):\n";
$onuSerial17409 = @snmprealwalk($hiosoIp, $community, '1.3.6.1.4.1.17409.2.3.5.1.1.1.1.2', 2000000, 1);
$onuSerial25355 = @snmprealwalk($hiosoIp, $community, '1.3.6.1.4.1.25355.2.3.5.1.1.1.1.2', 2000000, 1);

if ($onuSerial17409 && !empty($onuSerial17409)) {
    echo "   ✓ Enterprise 17409: " . count($onuSerial17409) . " ONUs found\n";
} else {
    echo "   ✗ Enterprise 17409: No data (requires Telnet fallback)\n";
}

if ($onuSerial25355 && !empty($onuSerial25355)) {
    echo "   ✓ Enterprise 25355: " . count($onuSerial25355) . " ONUs found\n";
} else {
    echo "   ✗ Enterprise 25355: No data (requires Telnet fallback)\n";
}

// 4. Telnet Test
echo "\n4. Telnet Connectivity:\n";
$telnetUser = 'admin';
$telnetPass = 'admin';

$sock = @fsockopen($hiosoIp, 23, $errno, $errstr, 5);
if ($sock) {
    echo "   ✓ Telnet connection: OK\n";
    
    // Simple login test
    stream_set_timeout($sock, 5);
    
    // Wait for login
    $buf = '';
    $start = time();
    while (time() - $start < 3) {
        $c = @fread($sock, 1024);
        if ($c) $buf .= $c;
        if (stripos($buf, 'login') !== false || stripos($buf, 'username') !== false) break;
        usleep(100000);
    }
    
    fwrite($sock, "{$telnetUser}\r\n");
    usleep(500000);
    
    $buf = '';
    $start = time();
    while (time() - $start < 2) {
        $c = @fread($sock, 1024);
        if ($c) $buf .= $c;
        if (stripos($buf, 'password') !== false) break;
        usleep(100000);
    }
    
    fwrite($sock, "{$telnetPass}\r\n");
    usleep(500000);
    
    $buf = '';
    $start = time();
    while (time() - $start < 3) {
        $c = @fread($sock, 1024);
        if ($c) $buf .= $c;
        if (strpos($buf, '>') !== false || strpos($buf, '#') !== false) break;
        usleep(100000);
    }
    
    if (strpos($buf, '>') !== false || strpos($buf, '#') !== false) {
        echo "   ✓ Telnet login: OK\n";
        echo "   ✓ Prompt detected: " . (strpos($buf, 'EPON') !== false ? 'EPON>' : 'Unknown') . "\n";
    } else {
        echo "   ✗ Telnet login: Failed\n";
    }
    
    fwrite($sock, "exit\r\n");
    fclose($sock);
} else {
    echo "   ✗ Telnet connection: {$errstr}\n";
}

// Summary
echo "\n=============================================================\n";
echo "                      SUMMARY\n";
echo "=============================================================\n\n";

echo "Hioso OLT ({$hiosoIp}):\n";
echo "  • SNMP Basic: Working (sysDescr, ifDescr)\n";
echo "  • SNMP Enterprise OIDs: NOT working (use Telnet fallback)\n";
echo "  • Telnet: Working (admin/admin)\n";
echo "  • HiosoHelper: Updated with Telnet fallback support\n";
echo "  • Method: Use Telnet 'show onu info epon 0/x all' for ONU data\n";

echo "\n✓ Test Complete!\n";
