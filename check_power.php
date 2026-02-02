<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$count = App\Models\Onu::whereNotNull('rx_power')->count();
$total = App\Models\Onu::count();
echo "ONU with RX Power: $count / $total\n\n";

$sample = App\Models\Onu::whereNotNull('rx_power')->take(10)->get();
foreach ($sample as $o) {
    echo "{$o->serial_number}: RX={$o->rx_power} dBm, TX={$o->tx_power} dBm\n";
}
