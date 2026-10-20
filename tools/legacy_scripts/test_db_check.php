<?php
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$onu = App\Models\Onu::where('serial_number', 'ZTEGD6D8B342')->latest()->first();
if ($onu) {
    echo json_encode([
        'sn' => $onu->serial_number,
        'onu_type' => $onu->onu_type,
        'slot' => $onu->slot,
        'port' => $onu->port,
        'onu_id' => $onu->onu_id,
        'config_status' => $onu->config_status,
        'vlan_config' => $onu->vlan_config,
        'name' => $onu->name,
    ], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "ONU not found\n";
}
