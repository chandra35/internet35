<?php
// Temp script to inspect raw SNMP data from ZTE C320
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

$host = '136.1.1.100';
$community = 'combro';
$base = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1';

$columns = [
    1 => 'Type',
    2 => 'Name',
    3 => 'Description',
    5 => 'AdminStatus',
    9 => 'LineProfile',
    10 => 'ServiceProfile',
    11 => 'RunStatus',
    12 => 'PhaseState',
    14 => 'SoftwareVer',
    15 => 'HardwareVer',
    18 => 'AuthInfo',
    20 => 'Distance',
];

// Walk each column for first ONU only to see all available fields
echo "=== ALL COLUMNS FOR FIRST 5 ONUs ===\n\n";

foreach ($columns as $col => $label) {
    $oid = "{$base}.{$col}";
    try {
        $data = @snmpwalkoid($host, $community, $oid, 5000000, 3);
        if ($data === false) {
            echo "Col {$col} ({$label}): WALK FAILED\n";
            continue;
        }
        echo "Col {$col} ({$label}):\n";
        $i = 0;
        foreach ($data as $k => $v) {
            echo "  " . ltrim($k, '.') . " => {$v}\n";
            if (++$i >= 5) break;
        }
        echo "  ... total: " . count($data) . " entries\n\n";
    } catch (Exception $e) {
        echo "Col {$col} ({$label}): ERROR - {$e->getMessage()}\n";
    }
}

// Also try walking other potential tables
echo "\n=== CHECKING OTHER ONU TABLES ===\n";
$otherOids = [
    'Table 4 (col 4)' => "{$base}.4",
    'Table 6 (SerialBin)' => "{$base}.6",
    'Table 7' => "{$base}.7",
    'Table 8' => "{$base}.8",
    'Table 13' => "{$base}.13",
    'Table 16' => "{$base}.16",
    'Table 17' => "{$base}.17",
    'Table 19' => "{$base}.19",
];

foreach ($otherOids as $label => $oid) {
    $data = @snmpwalkoid($host, $community, $oid, 5000000, 3);
    if ($data === false || empty($data)) {
        echo "{$label}: empty/failed\n";
        continue;
    }
    echo "{$label}:\n";
    $i = 0;
    foreach ($data as $k => $v) {
        echo "  " . ltrim($k, '.') . " => {$v}\n";
        if (++$i >= 3) break;
    }
    echo "  ... total: " . count($data) . " entries\n\n";
}

// Show full description for pattern analysis
echo "\n=== ALL DESCRIPTIONS (for pattern analysis) ===\n";
$descs = @snmpwalkoid($host, $community, "{$base}.3", 5000000, 3);
if ($descs) {
    foreach ($descs as $k => $v) {
        echo trim($v, '" ') . "\n";
    }
}
