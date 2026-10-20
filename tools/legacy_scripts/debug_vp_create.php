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

$script = 'return {writable: false, value: ["test", "xsd:string"]};';

// Test PUT with minimal script
echo "Test 1 - minimal script via asJson()..." . PHP_EOL;
$r = \Illuminate\Support\Facades\Http::timeout(10)
    ->asJson()
    ->put($nbiUrl . '/virtual_parameters/testParam', ['script' => $script]);
echo "HTTP {$r->status()}: " . $r->body() . PHP_EOL;

// Test 2 - check what body is actually sent
echo PHP_EOL . "Test 2 - same script via withBody..." . PHP_EOL;
$body = json_encode(['script' => $script]);
echo "Sending body: " . $body . PHP_EOL;
$r2 = \Illuminate\Support\Facades\Http::timeout(10)
    ->withBody($body, 'application/json')
    ->put($nbiUrl . '/virtual_parameters/testParam2');
echo "HTTP {$r2->status()}: " . $r2->body() . PHP_EOL;

// Test 3 - try inserting via direct mongo instead
echo PHP_EOL . "GET existing VP script to compare format..." . PHP_EOL;
$existing = \Illuminate\Support\Facades\Http::timeout(10)
    ->get($nbiUrl . '/virtual_parameters?limit=1');
$vps = $existing->json();
if (!empty($vps)) {
    echo "Sample VP script (first 100 chars): " . substr($vps[0]['script'] ?? '', 0, 100) . PHP_EOL;
    echo "Script JSON encoded first 100: " . substr(json_encode($vps[0]['script'] ?? ''), 0, 100) . PHP_EOL;
}
