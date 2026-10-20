<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = \App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$helper = (new \App\Helpers\Olt\ZteC320Helper())->setOlt($olt);

// Get raw output to see state format
$reflection = new ReflectionMethod($helper, 'executeBatchCliCommands');
$reflection->setAccessible(true);
// Build all detail-info commands
$onus = \App\Models\Onu::where('olt_id', $olt->id)->orderBy('slot')->orderBy('port')->orderBy('onu_id')->get();
$cmds = [];
foreach ($onus as $onu) {
    $cmds[] = "show gpon onu detail-info gpon-onu_1/{$onu->slot}/{$onu->port}:{$onu->onu_id}";
}
echo count($cmds) . " ONUs\n";
$t = microtime(true);
$output = $reflection->invoke($helper, $cmds);
$elapsed = round(microtime(true) - $t, 1);

// Extract distances
preg_match_all('/gpon-onu_1\/(\d+)\/(\d+):(\d+).*?ONU Distance:\s+(\d+)m/s', $output, $matches, PREG_SET_ORDER);
echo "=== {$elapsed}s, found " . count($matches) . " distances ===\n";
foreach ($matches as $m) {
    echo "  {$m[1]}/{$m[2]}:{$m[3]} = {$m[4]}m\n";
}
echo "=== END ===";
