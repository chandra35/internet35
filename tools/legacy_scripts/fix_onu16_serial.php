<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$onu = App\Models\Onu::where('slot',1)->where('port',1)->where('onu_id',16)->first();
if(!$onu){ echo "ONU 16 not found\n"; exit(1); }

echo "Before: serial_number={$onu->serial_number}\n";
$onu->serial_number = 'FHTT9B302530';
$onu->save();
echo "After:  serial_number={$onu->serial_number}\n";

// verify lookup now works
$svc = new App\Services\GenieAcsService();
$r = $svc->findDeviceBySerial($onu->serial_number);
echo "\nLookup result: " . ($r ? "FOUND device_id={$r['device_id']} model={$r['model']} last_inform={$r['last_inform']}" : "NOT FOUND") . "\n";
