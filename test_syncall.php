<?php
/**
 * Test syncAll method yang digunakan oleh web interface
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;
use Illuminate\Support\Facades\DB;

echo "=== TEST SYNC ALL METHOD ===\n\n";

// Use Hioso OLT 172.16.16.4
$olt = Olt::where('ip_address', '172.16.16.4')->first();
if (!$olt) {
    echo "ERROR: OLT not found!\n";
    exit(1);
}

echo "OLT: {$olt->name}\n";
echo "IP: {$olt->ip_address}\n";
echo "Brand: {$olt->brand}\n\n";

$helper = OltFactory::make($olt);

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

// Check DB for ONUs with optical data
echo "\n=== DATABASE CHECK ===\n";
$onusWithOptical = DB::table('onus')
    ->where('olt_id', $olt->id)
    ->whereNotNull('rx_power')
    ->count();

$onlineOnus = DB::table('onus')
    ->where('olt_id', $olt->id)
    ->where('status', 'online')
    ->count();

$totalOnus = DB::table('onus')
    ->where('olt_id', $olt->id)
    ->count();

echo "Total ONUs: {$totalOnus}\n";
echo "Online ONUs: {$onlineOnus}\n";
echo "ONUs with optical: {$onusWithOptical}\n";

// Sample 5 ONUs with optical
echo "\nSample ONUs with optical data:\n";
$samples = DB::table('onus')
    ->where('olt_id', $olt->id)
    ->whereNotNull('rx_power')
    ->limit(5)
    ->get();

foreach ($samples as $onu) {
    echo "  - ONU {$onu->slot}/{$onu->port}:{$onu->onu_id} ({$onu->description})\n";
    echo "    Status: {$onu->status}, Tx: {$onu->tx_power}dBm, Rx: {$onu->rx_power}dBm\n";
}

echo "\n=== DONE ===\n";
