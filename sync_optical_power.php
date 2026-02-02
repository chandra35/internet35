<?php
/**
 * Sync optical power untuk semua ONU
 */
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(0);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$olt = App\Models\Olt::first();
echo "Syncing optical power for OLT: {$olt->name}\n";

$ip = $olt->ip_address;
$community = $olt->snmp_community;

// Get all ONUs
$onus = App\Models\Onu::where('olt_id', $olt->id)->get();
echo "Found " . count($onus) . " ONUs\n\n";

$updated = 0;
$errors = 0;

foreach ($onus as $onu) {
    $port = $onu->port ?? $onu->pon_port ?? 1;
    $onuId = $onu->onu_id ?? $onu->onu_number ?? 0;
    
    // Skip if ONU ID is 0 (invalid)
    if ($onuId == 0) {
        continue;
    }
    
    // Get optical power
    $rxOid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.7.$port.$onuId";
    $txOid = "1.3.6.1.4.1.37950.1.1.5.12.2.1.8.1.6.$port.$onuId";
    
    $rxRaw = @snmpget($ip, $community, $rxOid, 1000000, 1);
    $txRaw = @snmpget($ip, $community, $txOid, 1000000, 1);
    
    $rxPower = null;
    $txPower = null;
    
    // Parse dBm from string "0.02 mW (-17.21 dBm)"
    if ($rxRaw && preg_match('/\(([+-]?[\d.]+)\s*dBm\)/i', $rxRaw, $m)) {
        $rxPower = round((float) $m[1], 2);
    }
    if ($txRaw && preg_match('/\(([+-]?[\d.]+)\s*dBm\)/i', $txRaw, $m)) {
        $txPower = round((float) $m[1], 2);
    }
    
    // Update database
    if ($rxPower !== null || $txPower !== null) {
        $onu->rx_power = $rxPower;
        $onu->tx_power = $txPower;
        $onu->olt_rx_power = $rxPower; // OLT RX = same as RX
        $onu->save();
        $updated++;
        
        echo ".";
        if ($updated % 50 == 0) {
            echo " $updated\n";
        }
    } else {
        $errors++;
    }
}

echo "\n\nDone! Updated: $updated, Skipped/Errors: $errors\n";
