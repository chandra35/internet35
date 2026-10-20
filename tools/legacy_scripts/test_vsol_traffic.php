<?php
/**
 * Test VSOL Traffic OIDs from LibreNMS MIB
 */

$ip = '172.16.16.3';
$community = 'public';

echo "Testing VSOL Traffic OIDs (from LibreNMS MIB)\n";
echo str_repeat("=", 70) . "\n\n";

// PON Port Performance Data - .37950.1.1.5.10.1.2.2.1.x
echo "PON Port Traffic (ponPortPerformanceDataEntry):\n";
echo str_repeat("-", 70) . "\n";

$ponBase = '1.3.6.1.4.1.37950.1.1.5.10.1.2.2.1';
$ponFields = [
    1 => 'ponPIndex',
    2 => 'ponPortUpOctets (Counter32)',
    22 => 'ponPortDownOctets (Counter32)',
    44 => 'ponPortDownBytes (Counter64)',
    45 => 'ponPortUpBytes (Counter64)',
];

foreach ($ponFields as $idx => $name) {
    echo "\n$name (.$idx):\n";
    $result = @snmpwalk($ip, $community, "$ponBase.$idx", 2000000, 3);
    if ($result && !empty($result)) {
        foreach ($result as $val) {
            echo "  $val\n";
        }
    } else {
        echo "  (no data)\n";
    }
}

echo "\n\n";
echo "Uplink Port Traffic (uplinkPortPerformanceDataEntry):\n";
echo str_repeat("-", 70) . "\n";

$uplinkBase = '1.3.6.1.4.1.37950.1.1.5.10.1.1.2.1';
$uplinkFields = [
    1 => 'upLinkPIndex',
    2 => 'upLinkPortDownOctets (Counter32)',
    18 => 'upLinkPortUpOctets (Counter32)',
    36 => 'uplinkPortDownBytes (Counter64)',
    37 => 'uplinkPortUpBytes (Counter64)',
];

foreach ($uplinkFields as $idx => $name) {
    echo "\n$name (.$idx):\n";
    $result = @snmpwalk($ip, $community, "$uplinkBase.$idx", 2000000, 3);
    if ($result && !empty($result)) {
        foreach ($result as $val) {
            echo "  $val\n";
        }
    } else {
        echo "  (no data)\n";
    }
}

echo "\n\nDone!\n";
