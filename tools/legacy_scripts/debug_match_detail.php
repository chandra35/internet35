<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\GenieAcsService;

$g = new GenieAcsService();
$devices = $g->getDevicesForSync();

// Build byHex map (same logic as command)
$byHex = [];
foreach ($devices as $id => $d) {
    if (!empty($d['serial_hex'])) {
        $byHex[strtoupper($d['serial_hex'])] = $id;
    }
    $parts = explode('-', $id, 3);
    if (isset($parts[2])) {
        $byHex[strtoupper($parts[2])] = $id;
    }
}

// Check GPON serials only
$onus = DB::table('onus')
    ->whereRaw("serial_number REGEXP '^[A-Za-z]{4}[0-9A-Fa-f]{8}$'")
    ->whereNull('deleted_at')
    ->get(['serial_number', 'status']);

$matched = 0;
$notMatched = [];
foreach ($onus as $onu) {
    $sn = strtoupper($onu->serial_number);
    
    // direct
    $found = isset($byHex[$sn]);
    if (!$found) {
        // hex convert
        $vendor = substr($sn, 0, 4);
        $serial = substr($sn, 4);
        $vendorHex = '';
        for ($i = 0; $i < strlen($vendor); $i++) {
            $vendorHex .= strtoupper(dechex(ord($vendor[$i])));
        }
        $hexSn = $vendorHex . strtoupper($serial);
        $found = isset($byHex[$hexSn]);
        if ($found) {
            echo "MATCH via hex: $sn -> $hexSn -> " . $byHex[$hexSn] . PHP_EOL;
        }
    } else {
        echo "MATCH direct: $sn -> " . $byHex[$sn] . PHP_EOL;
    }
    
    if ($found) {
        $matched++;
    } else {
        $notMatched[] = $sn;
    }
}

echo PHP_EOL . "Matched: $matched / " . count($onus) . PHP_EOL;
if ($notMatched) {
    echo "Not matched:" . PHP_EOL;
    foreach ($notMatched as $sn) echo "  $sn" . PHP_EOL;
}
