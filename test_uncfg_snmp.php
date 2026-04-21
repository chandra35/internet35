<?php
// Test SNMP walk on ZTE uncfg ONU table — run on VM with: /www/server/php/83/bin/php /tmp/test_uncfg_snmp.php

$oltIp = '136.1.1.100';
$community = 'public'; // adjust if needed

// Check community from DB
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = \App\Models\Olt::where('ip_address', $oltIp)->first();
if ($olt) {
    echo "OLT found: {$olt->name} (IP: {$olt->ip_address})\n";
    echo "SNMP community: '{$olt->snmp_community}'\n";
    $community = $olt->snmp_community ?: 'public';
} else {
    echo "OLT not found for IP $oltIp, using default community\n";
}

// Test each column of the uncfg table
$oids = [
    'col1' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.1',
    'col2_serial' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.2',
    'col3_model'  => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.3',
    'col4' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.4',
    'col5' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.5',
    'col6' => '1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.6',
];

// Also test CLI output
echo "\n=== CLI: show gpon onu uncfg ===\n";
try {
    $helper = \App\Helpers\Olt\OltFactory::make($olt);
    $uncfg = $helper->getUnregisteredOnus();
    echo "Found " . count($uncfg) . " uncfg ONUs:\n";
    foreach ($uncfg as $u) {
        echo "  SN={$u['serial_number']}  PON={$u['pon_port']}  type=" . ($u['onu_type'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== SNMP walks ===\n";
snmp_set_oid_numeric_print(true);
snmp_set_quick_print(true);

foreach ($oids as $name => $oid) {
    echo "\n--- $name ($oid) ---\n";
    $result = @snmpwalkoid($oltIp, $community, $oid, 5000000, 1);
    if ($result === false) {
        echo "  WALK FAILED (no data or timeout)\n";
    } elseif (empty($result)) {
        echo "  EMPTY (no entries)\n";
    } else {
        foreach ($result as $k => $v) {
            echo "  $k = $v\n";
        }
    }
}

// Also test: registered ONU type OID (zxAnGponOnuType) for comparison
echo "\n=== zxAnGponOnuType (registered ONUs, first 5) ===\n";
$typeOid = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.1';
$result = @snmpwalkoid($oltIp, $community, $typeOid, 5000000, 1);
if ($result === false || empty($result)) {
    echo "  WALK FAILED or EMPTY\n";
} else {
    $i = 0;
    foreach ($result as $k => $v) {
        echo "  $k = $v\n";
        if (++$i >= 5) { echo "  ... (more entries)\n"; break; }
    }
}
