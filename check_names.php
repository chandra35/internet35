<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get Hioso OLT
$olt = DB::table('olts')->where('ip_address', '172.16.16.4')->first();
echo "OLT ID: {$olt->id} - {$olt->name}\n\n";

$onus = DB::table('onus')
    ->where('olt_id', $olt->id)
    ->select('slot','port','onu_id','description','status')
    ->limit(20)
    ->get();

echo "=== ONU Names in Database ===\n\n";
foreach($onus as $o) {
    $name = $o->description ?: '(empty)';
    echo "ONU {$o->slot}/{$o->port}:{$o->onu_id} - Name: '{$name}' - Status: {$o->status}\n";
}
