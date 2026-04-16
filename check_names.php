<?php
snmp_set_quick_print(true);
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

$host = '136.1.1.100';
$community = 'combro';

// Col 18: Auth info (the primary walk source)
$authOid = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.18';
$authInfos = @snmpwalkoid($host, $community, $authOid, 5000000, 3);
echo "Auth info: " . count($authInfos) . " entries\n";

// Col 2: Name
$nameOid = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.2';
$names = @snmpwalkoid($host, $community, $nameOid, 5000000, 3);
echo "Names: " . count($names) . " entries\n";

// Show first 3 raw OID keys from auth
echo "\nAuth OID keys (first 3):\n";
$i = 0;
foreach ($authInfos as $oid => $val) {
    if ($i >= 3) break;
    echo "  key=[$oid]\n";
    $i++;
}

// Show first 3 raw OID keys from names
echo "\nName OID keys (first 3):\n";
$i = 0;
foreach ($names as $oid => $val) {
    if ($i >= 3) break;
    echo "  key=[$oid] val=[$val]\n";
    $i++;
}

// Test cross-reference
echo "\nCross-reference test:\n";
$i = 0;
foreach ($authInfos as $oid => $value) {
    if ($i >= 5) break;
    
    // Parse index
    preg_match('/\.(\d+)\.(\d+)$/', $oid, $m);
    $ponIfIndex = (int)$m[1];
    $onuId = (int)$m[2];
    $slot = ($ponIfIndex >> 8) & 0xFF;
    $port = $ponIfIndex & 0xFF;
    
    // Build index like code does
    $builtIndex = "{$ponIfIndex}.{$onuId}";
    $nameLookup = $nameOid . ".{$builtIndex}";
    $nameVal = $names[$nameLookup] ?? 'NOT FOUND';
    
    // Parse serial
    $trimmed = trim($value, " \t\n\r\0\x0B\"");
    $parts = explode(',', $trimmed, 2);
    $serial = isset($parts[1]) ? strtoupper(trim($parts[1])) : 'EMPTY';
    
    echo "  {$slot}/{$port}:{$onuId} serial=[$serial] name=[$nameVal] lookup_key=[$nameLookup]\n";
    $i++;
}

// Check DB values
echo "\nDB check:\n";
$pdo = new PDO("mysql:host=127.0.0.1;dbname=internet35", "internet35", "billing35db");
$stmt = $pdo->query("SELECT slot, port, onu_id, name, onu_type, status, description FROM onus WHERE olt_id IN (SELECT id FROM olts WHERE ip_address='136.1.1.100') LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['slot']}/{$row['port']}:{$row['onu_id']} name=[{$row['name']}] type=[{$row['onu_type']}] status=[{$row['status']}] desc=[{$row['description']}]\n";
}
