<?php
require '/www/wwwroot/internet35/vendor/autoload.php';
$app = require '/www/wwwroot/internet35/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if task already exists
$exists = DB::table('scheduled_tasks')
    ->where('command', 'onu:sync-genieacs')
    ->exists();

if ($exists) {
    echo "Task onu:sync-genieacs already exists!" . PHP_EOL;
} else {
    DB::table('scheduled_tasks')->insert([
        'id'          => (string) \Illuminate\Support\Str::uuid(),
        'name'        => 'Sync ONU dari GenieACS',
        'command'     => 'onu:sync-genieacs',
        'schedule'    => 'everyFiveMinutes',
        'is_enabled'  => 1,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
    echo "Task berhasil ditambahkan." . PHP_EOL;
}

// Show all tasks
$tasks = DB::table('scheduled_tasks')->get(['id', 'name', 'command', 'schedule', 'is_enabled']);
foreach ($tasks as $t) {
    echo "  [{$t->id}] {$t->name} | {$t->command} | {$t->schedule} | enabled={$t->is_enabled}" . PHP_EOL;
}
