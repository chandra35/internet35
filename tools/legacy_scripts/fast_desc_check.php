<?php
/**
 * Fast check - description biasanya di OID tertentu
 * Coba check langsung snmpwalk per tabel individual
 */

$oltIp = '172.16.16.3';
$community = 'private';

echo "=== FAST DESCRIPTION CHECK ===\n\n";

// Gunakan session dengan timeout lebih baik
$session = new SNMP(SNMP::VERSION_2C, $oltIp, $community, 5000000, 1);
$session->quick_print = true;
$session->valueretrieval = SNMP_VALUE_PLAIN;

// Coba walk beberapa table langsung
$tables = [
    'onuListTable.6' => '.1.3.6.1.4.1.37950.1.1.5.12.1.9.1.6',
    'onuListTable.7' => '.1.3.6.1.4.1.37950.1.1.5.12.1.9.1.7',
    'onuListTable.8' => '.1.3.6.1.4.1.37950.1.1.5.12.1.9.1.8',
    'onuListTable.9' => '.1.3.6.1.4.1.37950.1.1.5.12.1.9.1.9',
    'onuListTable.10' => '.1.3.6.1.4.1.37950.1.1.5.12.1.9.1.10',
];

echo "Checking onuListTable columns beyond column 5...\n\n";

foreach ($tables as $name => $oid) {
    echo "  {$name}: ";
    try {
        $result = @$session->walk($oid);
        if ($result && count($result) > 0) {
            echo count($result) . " entries\n";
            // Show first 3
            $i = 0;
            foreach ($result as $k => $v) {
                if ($i++ >= 3) break;
                echo "    Sample: {$v}\n";
            }
        } else {
            echo "no data\n";
        }
    } catch (Exception $e) {
        echo "error\n";
    }
}

// Check other possible description tables
echo "\n\nChecking other possible tables...\n\n";

$otherTables = [
    '12.1.3 onuMngTable' => '.1.3.6.1.4.1.37950.1.1.5.12.1.3',
    '12.1.5' => '.1.3.6.1.4.1.37950.1.1.5.12.1.5',
    '12.1.7' => '.1.3.6.1.4.1.37950.1.1.5.12.1.7',
    '12.2 onuCfgMgmt' => '.1.3.6.1.4.1.37950.1.1.5.12.2',
];

foreach ($otherTables as $name => $oid) {
    echo "  {$name}: ";
    try {
        $result = @$session->walk($oid);
        if ($result && count($result) > 0) {
            $textCount = 0;
            $sample = '';
            foreach ($result as $k => $v) {
                if (is_string($v) && preg_match('/[a-zA-Z]{4,}/', $v) && !preg_match('/^[0-9a-fA-F:.\-]+$/', $v)) {
                    $textCount++;
                    if (!$sample) $sample = $v;
                }
            }
            echo count($result) . " entries";
            if ($textCount > 0) {
                echo " (📝 {$textCount} text, sample: \"{$sample}\")";
            }
            echo "\n";
        } else {
            echo "no data\n";
        }
    } catch (Exception $e) {
        echo "error\n";
    }
}

$session->close();

echo "\n\nDone.\n";
