<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$onu = App\Models\Onu::where('serial_number', 'HWTC6ED42F9A')->first();
echo "ONU DB Data:\n";
echo "  id          : " . $onu->id . "\n";
echo "  name        : " . $onu->name . "\n";
echo "  slot/port/id: " . $onu->slot . "/" . $onu->port . "/" . $onu->onu_id . "\n";
echo "  line_profile: " . ($onu->line_profile ?? 'NULL') . "\n";
echo "  svc_profile : " . ($onu->service_profile ?? 'NULL') . "\n";
echo "  traffic_prof: " . ($onu->traffic_profile ?? 'NULL') . "\n";
echo "  pppoe_user  : " . ($onu->pppoe_username ?? 'NULL') . "\n";
echo "  vlan_config : " . json_encode($onu->vlan_config) . "\n";
echo "  onu_type    : " . ($onu->onu_type ?? 'NULL') . "\n";
echo "\nFull:\n";
print_r($onu->toArray());
