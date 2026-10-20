<?php
/**
 * Delete old ONUs and resync
 */
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Olt;
use App\Models\Onu;

$olt = Olt::where('ip_address', '172.16.16.3')->first();
if ($olt) {
    $deleted = Onu::where('olt_id', $olt->id)->forceDelete();
    echo "Deleted {$deleted} ONUs for OLT: {$olt->name}\n";
} else {
    echo "OLT not found\n";
}
