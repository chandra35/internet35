<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check how many ONU serials look like GPON format (4 alpha + 8 hex)
$gpon = DB::table('onus')
    ->whereRaw("serial_number REGEXP '^[A-Za-z]{4}[0-9A-Fa-f]{8}$'")
    ->whereNull('deleted_at')
    ->count();
echo "GPON-format serials (like HWTCCA9CD3A3): $gpon" . PHP_EOL;

// All other formats
$total = DB::table('onus')->whereNull('deleted_at')->count();
echo "Total ONU: $total" . PHP_EOL;

// Sample GPON ones
$samples = DB::table('onus')
    ->whereRaw("serial_number REGEXP '^[A-Za-z]{4}[0-9A-Fa-f]{8}$'")
    ->whereNull('deleted_at')
    ->limit(10)
    ->get(['serial_number', 'vendor', 'status']);
foreach ($samples as $s) {
    echo $s->serial_number . " | vendor=" . ($s->vendor ?? 'null') . " | status=" . $s->status . PHP_EOL;
}

// Also show distinct serial lengths
$lengths = DB::table('onus')
    ->whereNull('deleted_at')
    ->selectRaw('LENGTH(serial_number) as len, COUNT(*) as cnt')
    ->groupByRaw('LENGTH(serial_number)')
    ->orderByRaw('LENGTH(serial_number)')
    ->get();
echo PHP_EOL . "Serial length distribution:" . PHP_EOL;
foreach ($lengths as $l) {
    echo "  len={$l->len} : {$l->cnt} ONUs" . PHP_EOL;
}
