<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

foreach (App\Models\Olt::all() as $o) {
    echo "{$o->name} | {$o->brand} | {$o->ip_address}\n";
}
echo "\nVLANs:\n";
foreach (App\Models\OltVlan::all() as $v) {
    echo "  VLAN {$v->vlan_id} | {$v->name} | desc={$v->description} | tagged=" . json_encode($v->tagged_ports) . "\n";
}
