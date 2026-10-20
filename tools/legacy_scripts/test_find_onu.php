<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = '019d9dd9-e225-7058-96c6-30b1fdbf8708';

// Test 1: Direct find
$onu = App\Models\Onu::find($id);
echo "Test 1 - Onu::find(): " . ($onu ? 'FOUND: '.$onu->serial_number : 'NOT FOUND') . PHP_EOL;

// Test 2: With trashed
$onu2 = App\Models\Onu::withTrashed()->find($id);
echo "Test 2 - withTrashed()->find(): " . ($onu2 ? 'FOUND: '.$onu2->serial_number : 'NOT FOUND') . PHP_EOL;

// Test 3: Direct DB query
$row = Illuminate\Support\Facades\DB::table('onus')->where('id', $id)->first();
echo "Test 3 - DB::table(): " . ($row ? 'FOUND: '.$row->serial_number : 'NOT FOUND') . PHP_EOL;

// Test 4: resolveRouteBinding
$onu3 = (new App\Models\Onu())->resolveRouteBinding($id);
echo "Test 4 - resolveRouteBinding(): " . ($onu3 ? 'FOUND: '.$onu3->serial_number : 'NOT FOUND') . PHP_EOL;

// Test 5: Check key type
echo "Test 5 - Key type: " . (new App\Models\Onu())->getKeyType() . PHP_EOL;
echo "Test 5 - Incrementing: " . ((new App\Models\Onu())->getIncrementing() ? 'true' : 'false') . PHP_EOL;
