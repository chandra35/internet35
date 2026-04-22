<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = App\Models\Onu::where('serial_number', 'FHTT9B302530')->first();
echo json_encode([
    'status'      => $o->status,
    'olt_rx'      => $o->olt_rx_power,
    'rx'          => $o->rx_power,
    'tx'          => $o->tx_power,
    'sn'          => $o->serial_number,
    'updated_at'  => (string) $o->updated_at,
    'last_seen'   => (string) $o->last_seen_at,
    'signal_q'    => $o->signal_quality,
], JSON_PRETTY_PRINT) . PHP_EOL;
