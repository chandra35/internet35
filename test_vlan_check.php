<?php
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
echo "OLT: {$olt->name} (id={$olt->id})\n\n";

$vlans = App\Models\OltVlan::where('olt_id', $olt->id)->orderBy('vlan_id')->get(['vlan_id','name','type']);
echo "VLANs (" . count($vlans) . "):\n";
foreach ($vlans as $v) {
    echo "  VLAN {$v->vlan_id} — {$v->name} [{$v->type}]\n";
}
