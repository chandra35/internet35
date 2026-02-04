<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Olt;

$olt = Olt::where('brand', 'vsol')->first();
if (!$olt) {
    echo "No VSOL OLT found\n";
    exit(1);
}

$ip = $olt->ip_address;
$community = $olt->snmp_community ?? 'public';
$timeout = 2000000; // 2 seconds in microseconds

echo "Testing OLT: {$olt->name} ({$ip})\n";
echo "=".str_repeat("=", 50)."\n\n";

$oids = [
    'PON Down Bytes' => '1.3.6.1.4.1.37950.1.1.5.10.1.2.2.1.44',
    'PON Up Bytes' => '1.3.6.1.4.1.37950.1.1.5.10.1.2.2.1.45',
    'Uplink Down Bytes' => '1.3.6.1.4.1.37950.1.1.5.10.1.1.2.1.36',
    'Uplink Up Bytes' => '1.3.6.1.4.1.37950.1.1.5.10.1.1.2.1.37',
    'Optical Index' => '1.3.6.1.4.1.37950.1.1.5.10.13.1.1.1',
    'Optical Temp' => '1.3.6.1.4.1.37950.1.1.5.10.13.1.1.2',
    'Optical TxPower' => '1.3.6.1.4.1.37950.1.1.5.10.13.1.1.5',
];

snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

$totalTime = 0;

foreach ($oids as $name => $oid) {
    $start = microtime(true);
    $result = @snmpwalkoid($ip, $community, $oid, $timeout, 0);
    $elapsed = round((microtime(true) - $start) * 1000);
    $totalTime += $elapsed;
    
    $count = is_array($result) ? count($result) : 0;
    echo "{$name}:\n";
    echo "  OID: {$oid}\n";
    echo "  Time: {$elapsed}ms\n";
    echo "  Results: {$count}\n\n";
}

echo "=".str_repeat("=", 50)."\n";
echo "Total Time: {$totalTime}ms\n";
