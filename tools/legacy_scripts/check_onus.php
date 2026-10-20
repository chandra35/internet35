<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Onu;

echo "=== ONUs in Database ===\n\n";

$onus = Onu::with('olt')->get();
echo "Total ONUs: " . $onus->count() . "\n\n";

foreach ($onus as $onu) {
    echo "ID: {$onu->id}\n";
    echo "  OLT: " . ($onu->olt ? $onu->olt->name : 'N/A') . "\n";
    echo "  Port: {$onu->port}\n";
    echo "  ONU ID: {$onu->onu_id}\n";
    echo "  Serial: {$onu->serial_number}\n";
    echo "  MAC: {$onu->mac_address}\n";
    echo "  Status: {$onu->status}\n";
    echo "  Created: {$onu->created_at}\n";
    echo "\n";
}
