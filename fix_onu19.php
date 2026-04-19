<?php
/**
 * Fix ONU 19 in-place:
 * 1. Fix gpon-onu: service-port VLAN 355 → 335
 * 2. Fix pon-onu-mng: flow/vlan-filter 355→335, add pppoe/dhcp-ip/security-mgmt, remove bad vlan port line
 * 3. Fix DB: vlan_config.vlan_id 355 → 335
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$onu = App\Models\Onu::where('serial_number', 'HWTC6ED42F9A')->firstOrFail();

echo "=== ONU 19 DB data ===\n";
echo "pppoe_username: " . $onu->pppoe_username . "\n";
echo "pppoe_password: " . $onu->pppoe_password . "\n";
echo "vlan_config: " . json_encode($onu->vlan_config) . "\n";

// --- DRY RUN? Set to false to actually execute ---
$dryRun = isset($argv[1]) && $argv[1] === '--dry' ? true : false;
if ($dryRun) { echo "\n*** DRY RUN — no changes made ***\n\n"; }

$pppoeUser = $onu->pppoe_username;
$pppoePass = $onu->pppoe_password ?? '1234';
$mgmtVlan  = 111;
$newVlan   = 335;

$acsUrl  = config('services.genieacs.cwmp_url', 'http://172.10.10.254:7547');
$acsUser = config('services.genieacs.cwmp_username', '');
$acsPwd  = config('services.genieacs.cwmp_password', '');

// Build telnet commands
$cmds = [
    "configure terminal",

    // --- Fix gpon-onu service-port VLAN ---
    "interface gpon-onu_1/1/1:19",
    "no service-port 1",
    "service-port 1 vport 1 user-vlan {$newVlan} vlan {$newVlan}",
    "exit",

    // --- Fix pon-onu-mng ---
    "pon-onu-mng gpon-onu_1/1/1:19",

    // Remove wrong vlan port line
    "no vlan port eth_0/1 mode tag vlan 335",

    // Fix flow VLAN
    "flow 1 pri 0 vlan {$newVlan}",
    "vlan-filter iphost 1 pri 0 vlan {$newVlan}",

    // Add PPPoE
    "pppoe 1 nat enable user {$pppoeUser} password {$pppoePass}",

    // Add missing dhcp-ip
    "dhcp-ip ethuni eth_0/1 from-onu",
    "dhcp-ip ethuni eth_0/2 from-onu",
    "dhcp-ip ethuni eth_0/3 from-onu",
    "dhcp-ip ethuni eth_0/4 from-onu",

    // Add security-mgmt
    "security-mgmt 998 state enable mode forward ingress-type lan protocol web https",
    "security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069",

    "exit",
    "exit",
    "write",
];

echo "\n=== Commands to execute ===\n";
foreach ($cmds as $c) { echo "  $c\n"; }

if ($dryRun) {
    echo "\nDry run done. Run without --dry to execute.\n";
    exit(0);
}

// Execute via telnet
$olt = $onu->olt;
$helper = App\Helpers\Olt\OltFactory::make($olt);

// Use reflection to call protected method
$ref = new ReflectionMethod($helper, 'executeBatchCliCommands');
$ref->setAccessible(true);
$output = $ref->invoke($helper, $cmds);

echo "\n=== OLT Output ===\n$output\n";

// Check for errors
$hasError = stripos($output, 'Error') !== false || stripos($output, 'Invalid') !== false || stripos($output, 'fail') !== false;

if ($hasError) {
    echo "\n[WARNING] Possible error in output above. Check before updating DB.\n";
} else {
    // Fix DB vlan_config
    $vlanConfig = $onu->vlan_config;
    if (is_string($vlanConfig)) $vlanConfig = json_decode($vlanConfig, true);
    $vlanConfig['vlan_id'] = $newVlan;
    $onu->vlan_config = $vlanConfig;
    $onu->save();
    echo "\n[OK] DB vlan_config updated to vlan_id=335\n";
}
