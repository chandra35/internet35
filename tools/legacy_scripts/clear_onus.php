<?php
// Clear ONUs table
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Onu;

$count = Onu::count();
Onu::query()->forceDelete();
echo "Deleted {$count} ONUs from database\n";
