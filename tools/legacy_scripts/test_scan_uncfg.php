<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
if (!$olt) {
    $olt = App\Models\Olt::where('brand', 'like', '%ZTE%')->first();
}
echo 'OLT: ' . $olt->name . ' (id=' . $olt->id . ', ' . $olt->ip_address . ', brand=' . $olt->brand . ')' . PHP_EOL;

$helper = App\Helpers\Olt\OltFactory::make($olt);
$result = $helper->getUnregisteredOnus();
echo 'Count: ' . count($result) . PHP_EOL;
print_r($result);
