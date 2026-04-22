<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$h = App\Helpers\Olt\OltFactory::make($olt);
$ref = new ReflectionClass($h);
$exec = $ref->getMethod('executeBatchCliCommands');
$exec->setAccessible(true);

// Push pon-onu-mng manually with full output capture
$slot=1; $port=1; $onuId=16; $vlan=335; $mgmtVlan=111;
$acsUrl = 'http://172.10.10.254:7547';

$commands = [
    "configure terminal",
    "pon-onu-mng gpon-onu_1/{$slot}/{$port}:{$onuId}",
    "flow 2 switch switch_0/1",
    "flow mode 1 tag-filter vlan-filter untag-filter discard",
    "flow mode 2 tag-filter vlan-filter untag-filter discard",
    "flow 1 pri 0 vlan {$vlan}",
    "flow 2 pri 2 vlan {$mgmtVlan}",
    "gemport 1 flow 1",
    "gemport 2 flow 2",
    "switchport-bind switch_0/1 iphost 1",
    "switchport-bind switch_0/1 iphost 2",
    "switchport-bind switch_0/1 veip 1",
    "ip-host 2 dhcp-enable enable ping-response enable traceroute-response enable",
    "vlan-filter-mode iphost 1 tag-filter vlan-filter untag-filter discard",
    "vlan-filter-mode iphost 2 tag-filter vlan-filter untag-filter discard",
    "vlan-filter iphost 1 pri 0 vlan {$vlan}",
    "vlan-filter iphost 2 pri 2 vlan {$mgmtVlan}",
    "dhcp-ip ethuni eth_0/1 from-onu",
    "dhcp-ip ethuni eth_0/2 from-onu",
    "dhcp-ip ethuni eth_0/3 from-onu",
    "dhcp-ip ethuni eth_0/4 from-onu",
    "veip 1 port udp 1232 host 2",
    "tr069-mgmt 1 state unlock",
    "tr069-mgmt 1 acs {$acsUrl}",
    "tr069-mgmt 1 tag pri 2 vlan {$mgmtVlan}",
    "security-mgmt 998 state enable mode forward ingress-type lan protocol web https",
    "security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069",
    "exit",
    "exit",
    "write",
];

echo "=== Sending ".count($commands)." commands ===\n";
$out = $exec->invoke($h, $commands);
echo $out;
echo "\n\n=== END OUTPUT ===\n";
