<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** @var App\Models\Olt $olt */
$olt = App\Models\Olt::where('brand', 'zte')->first();
if (!$olt) { die("ZTE OLT tidak ditemukan di DB\n"); }

echo "OLT: " . $olt->name . " | " . $olt->ip_address . "\n";
echo "SNMP community: " . ($olt->snmp_community ?? 'N/A') . "\n";
echo "Telnet user: " . $olt->telnet_username . "\n";
echo json_encode($olt->toArray(), JSON_PRETTY_PRINT);
