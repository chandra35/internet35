<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = App\Models\Onu::where('serial_number', 'FHTT9B302530')->first();
echo "ONU id={$o->id} slot={$o->slot} port={$o->port} onu_id={$o->onu_id} olt_id={$o->olt_id}\n";

$olt = $o->olt;
echo "OLT brand={$olt->brand} ip={$olt->ip_address}\n";

$helper = App\Helpers\Olt\OltFactory::make($olt);
echo 'helper class: '.get_class($helper)."\n";

// Build SNMP index
$index = ($o->slot << 25) | ($o->port << 16) | $o->onu_id; // approximate ZTE index
// Actually use the helper's buildOnuIndex if exposed
$ref = new ReflectionClass($helper);
if ($ref->hasMethod('buildOnuIndex')) {
    $m = $ref->getMethod('buildOnuIndex');
    $m->setAccessible(true);
    $index = $m->invoke($helper, $o->slot, $o->port, $o->onu_id);
}
echo "snmp index = $index\n";

// Get the OID
$oidsProp = $ref->getProperty('zteOids');
$oidsProp->setAccessible(true);
$zteOids = $oidsProp->getValue($helper);

$runStatusOid = $zteOids['zxAnGponOnuRunStatus'].".$index";
echo "runStatus OID: $runStatusOid\n";

// Try snmpget
$community = $olt->snmp_community;
$ip = $olt->ip_address;
$out = shell_exec("snmpget -v2c -c '$community' -OQv -t 5 -r 1 $ip $runStatusOid 2>&1");
echo "snmpget result: ".trim($out)."\n";

// Also walk type
$typeOid = $zteOids['zxAnGponOnuType'].".$index";
$out2 = shell_exec("snmpget -v2c -c '$community' -OQv -t 5 -r 1 $ip $typeOid 2>&1");
echo "type: ".trim($out2)."\n";

$nameOid = $zteOids['zxAnGponOnuName'].".$index";
$out3 = shell_exec("snmpget -v2c -c '$community' -OQv -t 5 -r 1 $ip $nameOid 2>&1");
echo "name: ".trim($out3)."\n";
