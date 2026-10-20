<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find ONU 16 (fiberhome test)
$onu = App\Models\Onu::where('slot',1)->where('port',1)->where('onu_id',16)->first();
if(!$onu){ echo "ONU 16 not found\n"; exit; }
echo "ONU ID: {$onu->id}\n";
echo "Name: {$onu->name}\n";
echo "Serial stored in DB: {$onu->serial_number}\n";
echo "Type: {$onu->onu_type_id}\n";
echo "slot/port/onu_id: {$onu->slot}/{$onu->port}/{$onu->onu_id}\n";
echo "Created: {$onu->created_at}\n";
echo "Updated: {$onu->updated_at}\n";

// Test findDeviceBySerial with current DB serial
$svc = new App\Services\GenieAcsService();
$r = $svc->findDeviceBySerial($onu->serial_number);
echo "\nLookup with DB serial '{$onu->serial_number}': " . ($r ? "FOUND id={$r['id']}" : "NOT FOUND") . "\n";

// Test with correct OLT serial
$r2 = $svc->findDeviceBySerial('FHTT9B302530');
echo "Lookup with OLT serial 'FHTT9B302530': " . ($r2 ? "FOUND id={$r2['id']}" : "NOT FOUND") . "\n";
