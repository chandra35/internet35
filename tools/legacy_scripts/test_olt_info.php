<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$olt = App\Models\Olt::where('ip_address', '136.1.1.100')->first();
echo 'telnet_username: ' . $olt->telnet_username . PHP_EOL;
echo 'telnet_password: ' . $olt->telnet_password . PHP_EOL;
echo 'telnet_port: ' . $olt->telnet_port . PHP_EOL;
echo 'snmp_community: ' . $olt->snmp_community . PHP_EOL;

// Check supportsTelnet
$helper = App\Helpers\Olt\OltFactory::make($olt);
$ref = new ReflectionClass($helper);
$method = $ref->getMethod('supportsTelnet');
$method->setAccessible(true);
echo 'supportsTelnet: ' . ($method->invoke($helper) ? 'yes' : 'no') . PHP_EOL;

$method2 = $ref->getMethod('supportsSsh');
$method2->setAccessible(true);
echo 'supportsSsh: ' . ($method2->invoke($helper) ? 'yes' : 'no') . PHP_EOL;
