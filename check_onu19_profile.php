<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

/** @var App\Models\Olt $olt */
$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
if (!$olt) { die("ZTE OLT tidak ditemukan di DB\n"); }

/** @var App\Helpers\Olt\ZteC320Helper $helper */
$helper = App\Helpers\Olt\OltFactory::make($olt);

// 1. ONU Info (line_profile, service_profile via SNMP)
echo "=== getOnuInfo(1,1,19) ===\n";
$info = $helper->getOnuInfo(1, 1, 19);
echo "Line Profile   : " . ($info['line_profile'] ?? '-') . "\n";
echo "Service Profile: " . ($info['service_profile'] ?? '-') . "\n";
echo "Status         : " . ($info['status'] ?? '-') . "\n";
echo "Serial         : " . ($info['serial_number'] ?? '-') . "\n";
echo "\nFull info:\n";
print_r($info);

// 2. Available TCONT profiles
echo "\n=== getTcontProfiles ===\n";
$tcont = $helper->getTcontProfiles();
print_r($tcont);

// 3. Available Traffic profiles
echo "\n=== getTrafficProfiles ===\n";
$traffic = $helper->getTrafficProfiles();
print_r($traffic);

// 1. ONU Info (line_profile, service_profile via SNMP)
echo "=== getOnuInfo(1,1,19) ===\n";
$info = $helper->getOnuInfo(1, 1, 19);
echo "Line Profile   : " . ($info['line_profile'] ?? '-') . "\n";
echo "Service Profile: " . ($info['service_profile'] ?? '-') . "\n";
echo "Status         : " . ($info['status'] ?? '-') . "\n";
echo "Serial         : " . ($info['serial_number'] ?? '-') . "\n";
print_r($info);

// 2. Available TCONT profiles
echo "\n=== getTcontProfiles ===\n";
$tcont = $helper->getTcontProfiles();
print_r($tcont);

// 3. Available Traffic profiles
echo "\n=== getTrafficProfiles ===\n";
$traffic = $helper->getTrafficProfiles();
print_r($traffic);
