<?php
/**
 * Quick check VSOL interface status
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::where('brand', 'vsol')->first();
if (!$olt) { echo "No VSOL OLT\n"; exit; }

$output = "VSOL: {$olt->ip_address}\n\n";

$timeout = 2000000;
$community = $olt->snmp_community ?? 'public';

// Check interface status for index 1-20
$ifOperStatus = @snmpwalkoid($olt->ip_address, $community, '1.3.6.1.2.1.2.2.1.8', $timeout, 1) ?: [];

$output .= "Interface Status (ifOperStatus):\n";
foreach ($ifOperStatus as $oid => $val) {
    preg_match('/\.(\d+)$/', $oid, $m);
    $idx = $m[1] ?? '?';
    $status = (strpos($val, '1') !== false) ? 'UP' : 'DOWN';
    $output .= "  [$idx] $val => $status\n";
    if ($idx > 20) break;
}

// Also check PON port count from database
$output .= "\n\nDatabase PON ports:\n";
$ponPorts = $olt->ponPorts;
foreach ($ponPorts as $p) {
    $output .= "  Slot {$p->slot} Port {$p->port}: {$p->status}, ONUs: {$p->onu_count}\n";
}

file_put_contents(__DIR__ . '/vsol_status.txt', $output);
echo "Done - check vsol_status.txt\n";
