<?php
/**
 * Fix ONU offline yang stuck - update ke status terbaru dari OLT via SNMP
 * Jalankan: php artisan tinker --execute="require 'scripts/fix_offline_onus.php';"
 * Atau: php scripts/fix_offline_onus.php
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Olt;
use App\Models\Onu;
use App\Helpers\Olt\OltFactory;

$olts = Olt::whereNotNull('snmp_community')->whereNotNull('ip_address')->get();

foreach ($olts as $olt) {
    echo "OLT: {$olt->name} ({$olt->ip_address})\n";
    try {
        $helper = OltFactory::make($olt);
        if (!$helper->supportsSnmp()) {
            echo "  skip: SNMP not configured\n";
            continue;
        }

        $results = [];
        if (method_exists($helper, 'pollOnuRunStatus')) {
            $results = $helper->pollOnuRunStatus();
        } else {
            echo "  skip: pollOnuRunStatus not supported\n";
            continue;
        }
        echo "  SNMP poll: " . count($results) . " ONUs\n";

        $updated = 0;
        $now = now()->toDateTimeString();
        foreach ($results as $r) {
            $rows = $olt->onus()
                ->whereNull('deleted_at')
                ->where('slot',   $r['slot'])
                ->where('port',   $r['port'])
                ->where('onu_id', $r['onu_id'])
                ->where('status', '!=', $r['status']) // hanya yang beda
                ->get();

            foreach ($rows as $onu) {
                echo "  ONU {$r['slot']}/{$r['port']}/{$r['onu_id']} ({$onu->serial_number}): {$onu->status} → {$r['status']}\n";
                $upd = ['status' => $r['status'], 'updated_at' => $now];
                if ($r['status'] === 'online') {
                    $upd['last_online_at'] = $now;
                }
                $onu->update($upd);
                $updated++;
            }
        }
        echo "  Updated: {$updated} ONUs\n";

    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
