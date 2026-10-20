<?php
// Find the real uncfg ONU table by walking broader ZTE GPON subtrees
require_once '/www/wwwroot/internet35/vendor/autoload.php';
$app = require_once '/www/wwwroot/internet35/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$olt = \App\Models\Olt::where('ip_address', '136.1.1.100')->first();
$community = $olt->snmp_community ?: 'public';
echo "Using community: $community\n\n";

snmp_set_oid_numeric_print(true);
snmp_set_quick_print(true);

// The ZTE GPON base is 1.3.6.1.4.1.3902.1082.500.10.2.2
// Try subtrees under .2.2 (the management subtree)
$candidates = [
    '1.3.6.1.4.1.3902.1082.500.10.2.2.13',  // Common uncfg ONU location
    '1.3.6.1.4.1.3902.1082.500.10.2.2.14',  // Alternative
    '1.3.6.1.4.1.3902.1082.500.10.2.2.15',  // Alternative
    '1.3.6.1.4.1.3902.1082.500.10.2.2.16',  // Alternative
];

foreach ($candidates as $oid) {
    echo "=== Walking $oid ===\n";
    $result = @snmpwalkoid($olt->ip_address, $community, $oid, 5000000, 1);
    if ($result === false) {
        echo "  FAILED\n";
    } elseif (empty($result)) {
        echo "  EMPTY\n";
    } else {
        $i = 0;
        foreach ($result as $k => $v) {
            // Only show first 10 entries
            echo "  $k = $v\n";
            if (++$i >= 10) { echo "  ... (" . count($result) . " total entries)\n"; break; }
        }
    }
    echo "\n";
}

// Also try: walk a parent and look for "ZTEG" in values (the uncfg serial)
echo "=== Searching for serial ZTEGD6D8B342 in broader OID space ===\n";
// Walk the full .500.10.2.2 subtree looking for the serial
$bigWalk = @snmpwalkoid($olt->ip_address, $community, '1.3.6.1.4.1.3902.1082.500.10.2.2', 10000000, 1);
if ($bigWalk) {
    $found = 0;
    foreach ($bigWalk as $k => $v) {
        if (stripos($v, 'ZTEG') !== false || stripos($v, 'D6D8B342') !== false || stripos($v, 'F670') !== false) {
            echo "  MATCH: $k = $v\n";
            $found++;
        }
    }
    echo "  Searched " . count($bigWalk) . " OIDs, found $found matches\n";
} else {
    echo "  Big walk failed\n";
}

// Also search in .500.10.2.3 subtree
echo "\n=== Searching in .500.10.2.3 subtree ===\n";
$walk3 = @snmpwalkoid($olt->ip_address, $community, '1.3.6.1.4.1.3902.1082.500.10.2.3', 10000000, 1);
if ($walk3) {
    $found = 0;
    foreach ($walk3 as $k => $v) {
        if (stripos($v, 'ZTEG') !== false || stripos($v, 'D6D8B342') !== false || stripos($v, 'F670') !== false) {
            echo "  MATCH: $k = $v\n";
            $found++;
        }
    }
    echo "  Searched " . count($walk3) . " OIDs, found $found matches\n";
} else {
    echo "  Walk failed\n";
}

// Also try the broader parent: .500.10 (entire GPON)
echo "\n=== Searching in .500.10 subtree for serial/model ===\n";
$walkAll = @snmpwalkoid($olt->ip_address, $community, '1.3.6.1.4.1.3902.1082.500.10', 20000000, 3);
if ($walkAll) {
    $found = 0;
    foreach ($walkAll as $k => $v) {
        if (stripos($v, 'ZTEG') !== false || stripos($v, 'D6D8B342') !== false || stripos($v, 'F670') !== false || stripos($v, 'uncfg') !== false || stripos($v, 'unconfig') !== false) {
            echo "  MATCH: $k = $v\n";
            $found++;
        }
    }
    echo "  Searched " . count($walkAll) . " OIDs, found $found matches\n";
} else {
    echo "  Walk failed\n";
}
