<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$g = new App\Services\GenieAcsService();
echo "isAvailable: " . var_export($g->isAvailable(), true) . "\n";

$ref = new ReflectionClass($g);
$m = $ref->getMethod('shortSnToHex');
$m->setAccessible(true);
echo "shortSnToHex(HWTCD38C26AA) = " . var_export($m->invoke($g, 'HWTCD38C26AA'), true) . "\n";

$d = $g->findDeviceBySerial('HWTCD38C26AA');
echo "findDeviceBySerial result:\n";
print_r($d);
