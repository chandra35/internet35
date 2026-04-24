<?php
/**
 * Debug VP creation - check what error occurs
 */
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$nbiUrl = 'http://172.10.10.254:7557';
$script = 'return [Date.now(), "N/A"];';

// Test GET first
$r = \Illuminate\Support\Facades\Http::timeout(10)->get($nbiUrl . '/devices?limit=1');
echo "GET /devices: " . $r->status() . PHP_EOL;

// Test PUT with simple script
echo "PUT /virtual-parameters/getTxPower..." . PHP_EOL;
try {
    $response = \Illuminate\Support\Facades\Http::timeout(10)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->put($nbiUrl . '/virtual-parameters/getTxPower', ['script' => $script]);
    
    echo "HTTP status: " . $response->status() . PHP_EOL;
    echo "Body: " . $response->body() . PHP_EOL;
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}

// Also try with asJson
echo PHP_EOL . "Trying asJson()..." . PHP_EOL;
try {
    $response2 = \Illuminate\Support\Facades\Http::timeout(10)
        ->asJson()
        ->put($nbiUrl . '/virtual-parameters/getTxPower', ['script' => $script]);
    
    echo "HTTP status: " . $response2->status() . PHP_EOL;
    echo "Body: " . $response2->body() . PHP_EOL;
} catch (\Exception $e) {
    echo "Exception2: " . $e->getMessage() . PHP_EOL;
}

// Try with raw body
echo PHP_EOL . "Trying withBody(json)..." . PHP_EOL;
try {
    $response3 = \Illuminate\Support\Facades\Http::timeout(10)
        ->withBody(json_encode(['script' => $script]), 'application/json')
        ->put($nbiUrl . '/virtual-parameters/getTxPower');
    
    echo "HTTP status: " . $response3->status() . PHP_EOL;
    echo "Body: " . $response3->body() . PHP_EOL;
} catch (\Exception $e) {
    echo "Exception3: " . $e->getMessage() . PHP_EOL;
}
