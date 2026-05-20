<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$onu = App\Models\Onu::where('serial_number', 'HWTCD38C26AA')->first();
$rb = App\Helpers\Olt\OltFactory::make($onu->olt)->rebootOnu($onu->slot, $onu->port, $onu->onu_id);
echo "Reboot: ".json_encode($rb)."\n";
