<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$onu = App\Models\Onu::where('serial_number','HWTCC3875CB1')->first();
if(!$onu){ echo 'ONU not found'; exit; }
echo json_encode(['id'=>$onu->id,'onu_id'=>$onu->onu_id,'slot'=>$onu->slot,'port'=>$onu->port,'serial'=>$onu->serial_number,'vlan_config'=>$onu->vlan_config,'mgmt_ip'=>$onu->mgmt_ip], JSON_PRETTY_PRINT);