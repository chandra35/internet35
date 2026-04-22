<?php

namespace App\Console\Commands;

use App\Services\NewOnuDetectionService;
use Illuminate\Console\Command;

/**
 * Scheduler entry — usually wired via the `scheduled_tasks` table to
 * run every minute (it self-throttles per-POP using `unreg_notif_settings.scan_interval`).
 */
class ScanNewOnusCommand extends Command
{
    protected $signature = 'onus:scan-new';

    protected $description = 'Scan active OLTs for newly-detected unregistered ONUs and create in-app notifications';

    public function handle(NewOnuDetectionService $service): int
    {
        $start = microtime(true);
        $r = $service->scanAll();
        $ms = (int) ((microtime(true) - $start) * 1000);

        $this->info(sprintf(
            '[onus:scan-new] pops=%d olts=%d new=%d (%dms)',
            $r['pops_scanned'], $r['olts_scanned'], $r['notifications_created'], $ms
        ));

        return self::SUCCESS;
    }
}
