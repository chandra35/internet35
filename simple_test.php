<?php
/**
 * Simple test - write output directly to file
 */
file_put_contents(__DIR__ . '/simple_test.txt', "Test started at " . date('Y-m-d H:i:s') . "\n");

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;
use App\Helpers\Olt\OltFactory;

$output = "=== Simple VSOL Test ===\n\n";

try {
    $olt = Olt::where('brand', 'vsol')->first();
    
    if (!$olt) {
        $output .= "No VSOL OLT found!\n";
    } else {
        $output .= "OLT: {$olt->name} ({$olt->ip_address})\n\n";
        
        $helper = OltFactory::make($olt);
        $stats = $helper->getInterfaceStats();
        
        $output .= "Interface count: " . count($stats) . "\n\n";
        
        foreach ($stats as $s) {
            $output .= "- {$s['name']} ({$s['type']}): {$s['status']}, In={$s['in_bytes_formatted']}, Out={$s['out_bytes_formatted']}\n";
        }
    }
} catch (Exception $e) {
    $output .= "Error: " . $e->getMessage() . "\n";
    $output .= $e->getTraceAsString();
}

$output .= "\n=== DONE ===\n";

file_put_contents(__DIR__ . '/simple_test.txt', $output);
echo "Check simple_test.txt\n";
