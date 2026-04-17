<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::first();
echo 'OLT: ' . $olt->name . ' (' . $olt->ip_address . ')' . PHP_EOL;

$helper = App\Helpers\Olt\OltFactory::make($olt);
$result = $helper->getUnregisteredOnus();
echo 'Count: ' . count($result) . PHP_EOL;
print_r($result);
