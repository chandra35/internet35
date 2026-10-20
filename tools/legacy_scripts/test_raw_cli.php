<?php
// Get raw CLI output from 'show gpon onu uncfg'
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = \App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = \App\Helpers\Olt\OltFactory::make($olt);

// Use reflection to call the protected executeBatchCliCommands
$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

echo "=== RAW CLI: show gpon onu uncfg ===\n";
$raw = $ref->invoke($helper, ['show gpon onu uncfg']);
echo $raw . "\n";
echo "=== END RAW ===\n\n";

// Also try a detail command for uncfg
echo "=== RAW CLI: show gpon onu uncfg gpon-olt_1/1/1 ===\n";
$raw2 = $ref->invoke($helper, ['show gpon onu uncfg gpon-olt_1/1/1']);
echo $raw2 . "\n";
echo "=== END RAW ===\n\n";

// Also try the SNMP OID that worked: .2.2.5.1.7 (equipment ID for registered ONUs)
echo "=== SNMP .2.2.5.1.7 (EquipmentId) - first 10 ===\n";
snmp_set_oid_numeric_print(true);
snmp_set_quick_print(true);
$community = $olt->snmp_community ?: 'public';
$eqid = @snmpwalkoid($olt->ip_address, $community, '1.3.6.1.4.1.3902.1082.500.10.2.2.5.1.7', 5000000, 1);
if ($eqid) {
    $i = 0;
    foreach ($eqid as $k => $v) {
        echo "  $k = $v\n";
        if (++$i >= 10) { echo "  ... total: " . count($eqid) . "\n"; break; }
    }
} else {
    echo "  FAILED/EMPTY\n";
}
