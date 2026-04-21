<?php
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = App\Helpers\Olt\OltFactory::make($olt);

$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);

echo "=== show gpon onu profile type ===\n";
$out = $ref->invoke($helper, ['show gpon onu profile type']);
echo $out . "\n\n";

// Also check: is OPEN_ZTE profile different from F670L?
echo "=== show gpon onu profile type F670L ===\n";
$out = $ref->invoke($helper, ['show gpon onu profile type F670L']);
echo $out . "\n\n";

echo "=== show gpon onu profile type OPEN_ZTE ===\n";
$out = $ref->invoke($helper, ['show gpon onu profile type OPEN_ZTE']);
echo $out . "\n\n";

// Also: check if the ONU type difference affects PPPoE - check another ZTE ONU that works
echo "=== All ZTE ONUs on PON 1/1/1 - type check ===\n";
$out = $ref->invoke($helper, ['show running-config interface gpon-olt_1/1/1']);
$lines = explode("\n", $out);
foreach ($lines as $l) {
    if (preg_match('/onu\s+\d+\s+type\s+\S+\s+sn\s+ZTEG/i', $l)) {
        echo "  " . trim($l) . "\n";
    }
}
