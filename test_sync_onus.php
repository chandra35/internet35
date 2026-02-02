<?php
/**
 * Test ONU sync dengan OID baru yang terverifikasi
 * 
 * Test ini akan:
 * 1. Load OLT dari database
 * 2. Panggil VsolHelper->getAllOnus()
 * 3. Tampilkan hasil dengan detail
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\VsolHelper;
use Illuminate\Support\Facades\Log;

echo "=== TEST VSOL ONU SYNC WITH NEW OIDs ===\n\n";

// Get OLT
$olt = Olt::where('ip_address', '172.16.16.3')->first();

if (!$olt) {
    echo "ERROR: OLT not found!\n";
    exit(1);
}

echo "OLT: {$olt->name}\n";
echo "IP: {$olt->ip_address}\n";
echo "Brand: {$olt->brand}\n";
echo "Model: {$olt->model}\n";
echo "SNMP Community: {$olt->snmp_community}\n\n";

// Create helper instance
try {
    $helper = new VsolHelper();
    $helper->setOlt($olt);
    
    echo "Fetching ONUs...\n";
    $startTime = microtime(true);
    
    $onus = $helper->getAllOnus();
    
    $elapsed = round((microtime(true) - $startTime) * 1000);
    
    echo "Found " . count($onus) . " ONUs in {$elapsed}ms\n\n";
    
    if (count($onus) > 0) {
        echo "=== FIRST 20 ONUs ===\n";
        
        // Group by PON port
        $byPort = [];
        foreach ($onus as $onu) {
            $port = $onu['port'];
            if (!isset($byPort[$port])) {
                $byPort[$port] = [];
            }
            $byPort[$port][] = $onu;
        }
        
        ksort($byPort);
        
        $count = 0;
        foreach ($byPort as $port => $portOnus) {
            echo "\n--- PON Port {$port} (" . count($portOnus) . " ONUs) ---\n";
            foreach ($portOnus as $onu) {
                if ($count >= 20) break 2;
                
                echo sprintf(
                    "  ONU #%d: MAC=%s, Status=%s, Serial=%s\n",
                    $onu['onu_id'],
                    $onu['mac_address'] ?: 'N/A',
                    $onu['status'],
                    $onu['serial_number']
                );
                $count++;
            }
        }
        
        echo "\n=== PORT SUMMARY ===\n";
        foreach ($byPort as $port => $portOnus) {
            $online = count(array_filter($portOnus, fn($o) => $o['status'] === 'online'));
            echo "PON Port {$port}: " . count($portOnus) . " ONUs ({$online} online)\n";
        }
        
        // Check if MAC addresses are available
        $withMac = count(array_filter($onus, fn($o) => !empty($o['mac_address'])));
        echo "\n=== DATA QUALITY ===\n";
        echo "Total ONUs: " . count($onus) . "\n";
        echo "With MAC address: {$withMac}\n";
        echo "Without MAC: " . (count($onus) - $withMac) . "\n";
        
    } else {
        echo "No ONUs found!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
