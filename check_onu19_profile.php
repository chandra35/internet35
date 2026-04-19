<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$helper = new App\Helpers\Olt\ZteC320Helper([
    'host' => '136.1.1.100',
    'telnet_port' => 23,
    'username' => 'zte',
    'password' => 'zte',
]);

$cmds = [
    'show running-config interface gpon_onu 1/1/1:19',
    'show running-config interface gpon_onu 1/1/1:19 pon-onu-mng',
    'show pon onu service-port 1/1/1 onu 19',
    'show gpon onu tcont-info 1/1/1:19',
];

foreach ($cmds as $cmd) {
    echo "\n=== $cmd ===\n";
    $result = $helper->executeBatchCliCommands([$cmd]);
    echo $result;
}
