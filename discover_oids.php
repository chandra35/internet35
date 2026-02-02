<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;

$olt = Olt::first();

if (!$olt) {
    echo "No OLT found in database\n";
    exit;
}

echo "=== OLT: {$olt->name} ({$olt->ip_address}) ===\n\n";

snmp_set_quick_print(true);
snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

// Walk the entire legacy ONU tree to find what OIDs are available
$legacyBase = '.1.3.6.1.4.1.37950.1.1.5.12';

echo "Walking legacy ONU tree ($legacyBase)...\n\n";
$result = @snmp2_walk($olt->ip_address, $olt->snmp_community, $legacyBase, 10000000, 5);

if ($result !== false && !empty($result)) {
    // Group by table/column
    $grouped = [];
    foreach ($result as $oid => $val) {
        // Parse OID structure: .1.3.6.1.4.1.37950.1.1.5.12.X.X.X.Y.port.onuid
        if (preg_match('/\.1\.3\.6\.1\.4\.1\.37950\.1\.1\.5\.12\.(\d+)\.(\d+)\.(\d+)\.(\d+)(?:\.(\d+)\.(\d+))?/', $oid, $m)) {
            $path = ".12.{$m[1]}.{$m[2]}.{$m[3]}.{$m[4]}";
            $index = isset($m[5]) ? "{$m[5]}.{$m[6]}" : "";
            $grouped[$path][$index] = [
                'oid' => $oid,
                'value' => $val
            ];
        }
    }
    
    foreach ($grouped as $path => $entries) {
        echo "=== OID Path: 1.3.6.1.4.1.37950.1.1.5{$path} ===\n";
        foreach ($entries as $idx => $data) {
            $displayVal = strlen($data['value']) > 50 ? substr($data['value'], 0, 50) . '...' : $data['value'];
            echo "  [{$idx}] = {$displayVal}\n";
        }
        echo "\n";
    }
    
    echo "\nTotal OIDs: " . count($result) . "\n";
} else {
    echo "FAILED to walk legacy tree!\n";
    
    // Try to find ANY enterprise OID
    echo "\nTrying to walk entire enterprise tree...\n";
    $result = @snmp2_walk($olt->ip_address, $olt->snmp_community, '.1.3.6.1.4.1', 30000000, 5);
    
    if ($result !== false && !empty($result)) {
        $enterprises = [];
        foreach ($result as $oid => $val) {
            if (preg_match('/\.1\.3\.6\.1\.4\.1\.(\d+)/', $oid, $m)) {
                $enterprises[$m[1]] = ($enterprises[$m[1]] ?? 0) + 1;
            }
        }
        
        echo "Found enterprises:\n";
        arsort($enterprises);
        foreach ($enterprises as $id => $count) {
            echo "  Enterprise $id: $count OIDs\n";
        }
    } else {
        echo "SNMP completely failed\n";
    }
}
