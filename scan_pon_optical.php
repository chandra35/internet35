<?php
/**
 * Scan PON Port Optical Power OIDs for Hioso and VSOL OLTs
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;

// Test both OLTs
$olts = [
    ['ip' => '172.16.16.4', 'name' => 'Hioso', 'community' => 'tahsin'],
    ['ip' => '172.16.16.3', 'name' => 'VSOL', 'community' => 'tahsin'],
];

// Common PON optical power OID patterns to try
$oidsToTry = [
    // Hioso Enterprise 25355
    'hioso_pon_tx_power' => '1.3.6.1.4.1.25355.2.3.4.1.1.1.1',  // PON port table
    'hioso_pon_optical' => '1.3.6.1.4.1.25355.2.3.4.1.2',        // PON optical
    'hioso_sfp_info' => '1.3.6.1.4.1.25355.2.3.4.1.3',           // SFP info
    'hioso_pon_diag' => '1.3.6.1.4.1.25355.2.3.4.1.4',           // PON diagnostics
    
    // Alternative Hioso OIDs 17409
    'hioso17409_pon' => '1.3.6.1.4.1.17409.2.3.4.1.1.1.1',
    'hioso17409_optical' => '1.3.6.1.4.1.17409.2.3.4.1.2',
    'hioso17409_sfp' => '1.3.6.1.4.1.17409.2.3.4.1.3',
    
    // VSOL Enterprise 37950
    'vsol_pon_port' => '1.3.6.1.4.1.37950.1.1.5.11.1.1.1',       // PON port table
    'vsol_pon_optical' => '1.3.6.1.4.1.37950.1.1.5.11.1.2',      // PON optical?
    'vsol_sfp' => '1.3.6.1.4.1.37950.1.1.5.11.2',                // SFP?
    'vsol_pon_diag' => '1.3.6.1.4.1.37950.1.1.5.11.3',           // PON diag?
    'vsol_optical' => '1.3.6.1.4.1.37950.1.1.5.13',              // Optical module?
    
    // Entity MIB for transceiver/SFP info
    'entity_physical' => '1.3.6.1.2.1.47.1.1.1.1',               // entPhysicalTable
    'entity_sensor' => '1.3.6.1.2.1.99.1.1.1',                   // entitySensorMIB
    
    // IF-MIB extensions for optical
    'ifMIB' => '1.3.6.1.2.1.31.1.1.1',                           // ifXTable
];

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

foreach ($olts as $olt) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "OLT: {$olt['name']} ({$olt['ip']})\n";
    echo str_repeat("=", 60) . "\n";
    
    foreach ($oidsToTry as $name => $oid) {
        echo "\n--- Trying: $name ($oid) ---\n";
        
        try {
            $result = @snmpwalkoid($olt['ip'], $olt['community'], $oid, 2000000, 2);
            
            if ($result && count($result) > 0) {
                echo "FOUND " . count($result) . " entries:\n";
                $count = 0;
                foreach ($result as $fullOid => $value) {
                    // Clean OID
                    $shortOid = str_replace('iso.3.6.1', '1.3.6.1', $fullOid);
                    echo "  $shortOid = $value\n";
                    $count++;
                    if ($count >= 20) {
                        echo "  ... (truncated, " . count($result) . " total)\n";
                        break;
                    }
                }
            } else {
                echo "  (no data)\n";
            }
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n\nDone!\n";
