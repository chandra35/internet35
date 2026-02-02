<?php
/**
 * Test syncAll method yang digunakan oleh web interface
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\VsolHelper;

echo "=== TEST SYNC ALL METHOD ===\n\n";

$olt = Olt::where('ip_address', '172.16.16.3')->first();
if (!$olt) {
    echo "ERROR: OLT not found!\n";
    exit(1);
}

echo "OLT: {$olt->name}\n";
echo "IP: {$olt->ip_address}\n\n";

$helper = new VsolHelper();
$helper->setOlt($olt);

echo "Running syncAll()...\n";
$startTime = microtime(true);

$result = $helper->syncAll();

$elapsed = round(microtime(true) - $startTime, 2);

echo "\n=== RESULT ===\n";
echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "PON Ports Synced: {$result['pon_ports_synced']}\n";
echo "ONUs Synced: {$result['onus_synced']}\n";
echo "Signals Recorded: {$result['signals_recorded']}\n";
echo "Errors: " . count($result['errors']) . "\n";

if (!empty($result['errors'])) {
    echo "\nError details (first 5):\n";
    foreach (array_slice($result['errors'], 0, 5) as $err) {
        echo "  - {$err}\n";
    }
}

echo "\nTime: {$elapsed}s\n";
echo "\n=== DONE ===\n";
