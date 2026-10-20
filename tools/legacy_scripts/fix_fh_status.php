<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = App\Models\Onu::where('serial_number', 'FHTT9B302530')->first();
echo "before status={$o->status}\n";

$helper = App\Helpers\Olt\OltFactory::make($o->olt);
$info = $helper->getOnuInfo($o->slot, $o->port, $o->onu_id);
echo "live getOnuInfo: ".json_encode($info)."\n";

if (!empty($info['status']) && $info['status'] !== 'unknown') {
    $o->update(['status' => $info['status']]);
    echo "updated DB status -> {$info['status']}\n";
}
echo "after status=".$o->fresh()->status."\n";
