<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\VsolHelper;

$olt = Olt::where('status', 'active')->first();
echo "Syncing OLT: {$olt->name}\n";

$helper = (new VsolHelper())->setOlt($olt);
$result = $helper->syncAll();

echo "ONUs synced: {$result['onus_synced']}\n";

// Check new distances
$onus = App\Models\Onu::whereNotNull('distance')
    ->where('distance', '>', 0)
    ->take(10)
    ->get(['port', 'onu_id', 'description', 'distance']);

echo "\nUpdated distances:\n";
foreach ($onus as $o) {
    echo "  P{$o->port}/ONU{$o->onu_id}: {$o->description} -> {$o->distance}m\n";
}
