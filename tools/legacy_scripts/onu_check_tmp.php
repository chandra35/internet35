<?php
chdir('/www/wwwroot/internet35');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$olt = App\Models\Olt::where('ip_address','136.1.1.100')->first();
$h = new App\Helpers\Olt\ZteC320Helper($olt);
$out = $h->executeBatchCliCommands(['show gpon onu detail-info gpon-onu_1/1/1:21','show pon-onu-mng gpon-onu_1/1/1:21']);
echo $out;
