<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = App\Models\Onu::where('serial_number','HWTCD38C26AA')->first();
if(!$o){ echo "ONU NOT FOUND\n"; exit; }
$olt = $o->olt;
echo json_encode([
  'onu' => $o->only(['id','name','serial_number','slot','port','onu_id','management_ip','vlan_id','management_vlan','line_profile','service_profile','status','olt_id']),
  'olt' => [
    'name' => $olt->name,
    'host' => $olt->ip_address ?? null,
    'telnet_port' => $olt->telnet_port ?? null,
    'telnet_user' => $olt->telnet_username ?? null,
    'telnet_pass' => $olt->telnet_password ?? null,
    'enable_pass' => $olt->enable_password ?? null,
  ],
], JSON_PRETTY_PRINT)."\n";
