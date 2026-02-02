<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(0);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$olt = App\Models\Olt::first();
echo "OLT: {$olt->name} ({$olt->ip_address})\n\n";

// Test optical power untuk beberapa ONU
$ip = $olt->ip_address;
$community = $olt->snmp_community;

// Ambil beberapa sample dari database
$onus = App\Models\Onu::where('olt_id', $olt->id)->take(5)->get();

echo "Testing optical power for " . count($onus) . " ONUs:\n";
echo str_repeat('-', 70) . "\n";

foreach ($onus as $onu) {
    $port = $onu->port ?? $onu->pon_port ?? 1;
    $onuId = $onu->onu_id ?? $onu->onu_number ?? 1;
    
    // OID format: .12.2.1.8.1.7.{ponId}.{onuId} for RX
    // OID format: .12.2.1.8.1.6.{ponId}.{onuId} for TX
    $rxOid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.7.$port.$onuId";
    $txOid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.6.$port.$onuId";
    
    $rxRaw = @snmpget($ip, $community, $rxOid, 2000000, 1);
    $txRaw = @snmpget($ip, $community, $txOid, 2000000, 1);
    
    echo "ONU {$onu->serial_number} (PON $port, ONU $onuId):\n";
    echo "  RX OID: $rxOid\n";
    echo "  RX Raw: " . ($rxRaw !== false ? $rxRaw : 'NULL') . "\n";
    echo "  TX Raw: " . ($txRaw !== false ? $txRaw : 'NULL') . "\n";
    
    // Parse dBm value from string like "0.02 mW (-17.21 dBm)"
    if ($rxRaw && preg_match('/\(([+-]?[\d.]+)\s*dBm\)/', $rxRaw, $m)) {
        echo "  RX Parsed: {$m[1]} dBm\n";
    }
    if ($txRaw && preg_match('/\(([+-]?[\d.]+)\s*dBm\)/', $txRaw, $m)) {
        echo "  TX Parsed: {$m[1]} dBm\n";
    }
    echo "\n";
}

echo "Done.\n";
