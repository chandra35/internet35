<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Suppress MIB warnings
error_reporting(E_ALL & ~E_WARNING);

echo "=== Testing OltFactory::identify() ===\n\n";

// Test with Hioso OLT
$ip = $argv[1] ?? '172.16.16.4';
$community = $argv[2] ?? 'public';

echo "Testing identify on {$ip} (community: {$community})...\n\n";
$result = App\Helpers\Olt\OltFactory::identify($ip, 161, $community);

echo "Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
echo "Brand: " . ($result['brand'] ?? '-') . "\n";
echo "Brand Label: " . ($result['brand_label'] ?? '-') . "\n";
echo "Model: " . ($result['model'] ?? '-') . "\n";
echo "PON Ports: " . ($result['total_pon_ports'] ?? 0) . "\n";
echo "Uplink Ports: " . ($result['total_uplink_ports'] ?? 0) . "\n";
echo "Description: " . ($result['description'] ?? '-') . "\n";
echo "Message: " . ($result['message'] ?? '-') . "\n";

// Debug: show what sysObjectID returned
echo "\n=== Debug SNMP ===\n";
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
$sysObjectId = @snmpget($ip, $community, '1.3.6.1.2.1.1.2.0', 5000000, 2);
echo "sysObjectID: " . ($sysObjectId ?: 'N/A') . "\n";

// Extract enterprise ID
if ($sysObjectId && preg_match('/(?:iso|\.?1)\.3\.6\.1\.4\.1\.(\d+)/', $sysObjectId, $matches)) {
    echo "Enterprise ID: " . $matches[1] . "\n";
}
