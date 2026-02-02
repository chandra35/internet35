<?php
/**
 * Sync ONUs ke database dengan OID baru
 * 
 * Script ini akan:
 * 1. Load semua ONU dari OLT via SNMP
 * 2. Update/Create ONU di database
 * 3. Tampilkan summary
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use App\Models\Onu;
use App\Helpers\Olt\VsolHelper;
use Illuminate\Support\Facades\Log;

echo "=== SYNC ONUs TO DATABASE ===\n\n";

// Get OLT
$olt = Olt::where('ip_address', '172.16.16.3')->first();

if (!$olt) {
    echo "ERROR: OLT not found!\n";
    exit(1);
}

echo "OLT: {$olt->name}\n";
echo "IP: {$olt->ip_address}\n\n";

// Create helper instance
try {
    $helper = new VsolHelper();
    $helper->setOlt($olt);
    
    echo "Fetching ONUs from OLT...\n";
    $startTime = microtime(true);
    
    $onus = $helper->getAllOnus();
    
    $fetchTime = round((microtime(true) - $startTime) * 1000);
    echo "Found " . count($onus) . " ONUs in {$fetchTime}ms\n\n";
    
    if (count($onus) == 0) {
        echo "No ONUs found, nothing to sync.\n";
        exit(0);
    }
    
    // Get existing ONUs from database
    $existingOnus = Onu::where('olt_id', $olt->id)->get()->keyBy('serial_number');
    echo "Existing ONUs in database: " . $existingOnus->count() . "\n\n";
    
    // Get PON ports
    $ponPorts = \App\Models\OltPonPort::where('olt_id', $olt->id)->get()->keyBy(function($item) {
        return $item->slot . '-' . $item->port;
    });
    
    $created = 0;
    $updated = 0;
    $errors = [];
    
    echo "Syncing ONUs to database...\n";
    
    foreach ($onus as $onuData) {
        try {
            $serial = $onuData['serial_number'];
            
            // Find PON port
            $portKey = ($onuData['slot'] ?? 0) . '-' . $onuData['port'];
            $ponPort = $ponPorts->get($portKey);
            
            // If PON port doesn't exist, create it
            if (!$ponPort) {
                $ponPort = \App\Models\OltPonPort::create([
                    'olt_id' => $olt->id,
                    'slot' => $onuData['slot'] ?? 0,
                    'port' => $onuData['port'],
                    'admin_status' => 'enabled',
                    'status' => 'up',
                    'last_sync_at' => now(),
                ]);
                $ponPorts->put($portKey, $ponPort);
            }
            
            $exists = $existingOnus->has($serial);
            
            // Prepare data - include all required fields
            $data = [
                'olt_id' => $olt->id,
                'pon_port_id' => $ponPort->id,
                'serial_number' => $serial,
                'mac_address' => $onuData['mac_address'] ?? null,
                'slot' => $onuData['slot'] ?? 0,
                'port' => $onuData['port'],
                'onu_id' => $onuData['onu_id'] ?? 1,
                'status' => $onuData['status'] ?? 'unknown',
                'description' => $onuData['description'] ?? null,
                'last_sync_at' => now(),
            ];
            
            if ($exists) {
                // Update existing ONU
                $onu = $existingOnus->get($serial);
                $onu->update($data);
                $updated++;
            } else {
                // Create new ONU
                Onu::create($data);
                $created++;
            }
            
        } catch (Exception $e) {
            $errors[] = "Error on serial {$serial}: " . $e->getMessage();
        }
    }
    
    echo "\n=== SYNC COMPLETE ===\n";
    echo "Created: {$created}\n";
    echo "Updated: {$updated}\n";
    echo "Errors: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\nError details:\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "  - {$error}\n";
        }
        if (count($errors) > 10) {
            echo "  ... and " . (count($errors) - 10) . " more errors\n";
        }
    }
    
    // Show summary from database
    echo "\n=== DATABASE SUMMARY ===\n";
    $totalOnus = Onu::where('olt_id', $olt->id)->count();
    $onlineOnus = Onu::where('olt_id', $olt->id)->where('status', 'online')->count();
    $withMac = Onu::where('olt_id', $olt->id)->whereNotNull('mac_address')->where('mac_address', '!=', '')->count();
    
    echo "Total ONUs in DB: {$totalOnus}\n";
    echo "Online: {$onlineOnus}\n";
    echo "With MAC address: {$withMac}\n";
    
    // Show sample
    echo "\n=== SAMPLE ONUs (first 10) ===\n";
    $samples = Onu::where('olt_id', $olt->id)
        ->orderBy('onu_id')
        ->limit(10)
        ->get();
    
    foreach ($samples as $onu) {
        echo sprintf(
            "  ONU#%d (Port %d): MAC=%s, Status=%s, Serial=%s\n",
            $onu->onu_id ?? 0,
            $onu->port ?? 0,
            $onu->mac_address ?: 'N/A',
            $onu->status,
            $onu->serial_number
        );
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nDone!\n";
