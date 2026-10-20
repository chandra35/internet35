<?php
error_reporting(0);
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
$ip = '172.16.16.3';
$c = 'private';

// ONU side - onuOpmDiagTable (.12.2.1.13)
$onuTx = @snmpwalkoid($ip, $c, '1.3.6.1.4.1.37950.1.1.5.12.2.1.13.1.6', 3000000, 1);
echo "ONU TX Power (.12.2.1.13.1.6): " . ($onuTx ? count($onuTx) : 0) . " entries\n";
if ($onuTx) { $i=0; foreach($onuTx as $o=>$v) { if($i++<3) echo "  $o = $v\n"; } }

$onuRx = @snmpwalkoid($ip, $c, '1.3.6.1.4.1.37950.1.1.5.12.2.1.13.1.7', 3000000, 1);
echo "\nONU RX Power (.12.2.1.13.1.7): " . ($onuRx ? count($onuRx) : 0) . " entries\n";
if ($onuRx) { $i=0; foreach($onuRx as $o=>$v) { if($i++<3) echo "  $o = $v\n"; } }
