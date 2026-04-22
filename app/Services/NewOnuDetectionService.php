<?php

namespace App\Services;

use App\Helpers\Olt\OltFactory;
use App\Models\Olt;
use App\Models\PopSetting;
use App\Models\User;
use App\Notifications\NewOnuDetected;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Scans active OLTs for unregistered ONUs and fires a NewOnuDetected
 * notification for each ONU that wasn't seen on the previous scan.
 *
 * Per-POP throttle: each POP scans at its own configured interval
 * (`unreg_notif_settings.scan_interval`, default 60 s) regardless of
 * how often the cron itself ticks (typically every minute).
 *
 * Per-POP filter: if `unreg_notif_settings.olts` is non-empty, only
 * those OLT ids are scanned for that POP.
 *
 * Multi-brand isolation: this service touches NO brand-specific code.
 * All brand quirks live behind `OltFactory::make($olt)->getUnregisteredOnus()`.
 */
class NewOnuDetectionService
{
    /** Cache TTL for the "seen serials" set per OLT (seconds). */
    protected const SEEN_TTL = 86400; // 24h — long enough to outlive a temporary OLT outage

    /**
     * Scan every active POP that has the feature enabled.
     *
     * @return array{pops_scanned:int, olts_scanned:int, notifications_created:int}
     */
    public function scanAll(): array
    {
        $popsScanned = 0;
        $oltsScanned = 0;
        $notifsCreated = 0;

        // Only POPs that have a popSetting row + are an admin-pop user
        $popUsers = User::role('admin-pop')->with('popSetting')->get();

        foreach ($popUsers as $user) {
            $setting = $user->popSetting;
            if (!$setting) {
                $setting = PopSetting::getOrCreateForUser($user->id);
            }

            if (!$setting->unregNotifSetting('enabled')) continue;

            // Throttle: skip if last scan was more recent than scan_interval
            $interval = (int) $setting->unregNotifSetting('scan_interval', 60);
            if ($setting->last_unreg_notif_scan_at
                && $setting->last_unreg_notif_scan_at->diffInSeconds(now()) < $interval) {
                continue;
            }

            $result = $this->scanPop($user, $setting);
            $popsScanned++;
            $oltsScanned   += $result['olts_scanned'];
            $notifsCreated += $result['notifications_created'];

            $setting->forceFill(['last_unreg_notif_scan_at' => now()])->save();
        }

        return [
            'pops_scanned'          => $popsScanned,
            'olts_scanned'          => $oltsScanned,
            'notifications_created' => $notifsCreated,
        ];
    }

    /**
     * Scan all OLTs of a single POP (= admin-pop user).
     *
     * @return array{olts_scanned:int, notifications_created:int}
     */
    public function scanPop(User $popUser, PopSetting $setting): array
    {
        $oltFilter = (array) $setting->unregNotifSetting('olts', []);

        $query = Olt::query()->forPop($popUser->id)->active();
        if (!empty($oltFilter)) {
            $query->whereIn('id', $oltFilter);
        }

        $olts = $query->get();
        $oltsScanned = 0;
        $notifsCreated = 0;

        foreach ($olts as $olt) {
            $oltsScanned++;
            $notifsCreated += $this->scanOlt($olt, $popUser);
        }

        return [
            'olts_scanned'          => $oltsScanned,
            'notifications_created' => $notifsCreated,
        ];
    }

    /**
     * Scan one OLT: diff current unregistered ONUs against the cached snapshot
     * and notify the POP user about any newcomers.
     *
     * @return int  number of notifications created
     */
    public function scanOlt(Olt $olt, User $popUser): int
    {
        try {
            $helper = OltFactory::make($olt);
            $unreg = $helper->getUnregisteredOnus(); // each item must contain 'serial_number'
        } catch (\Throwable $e) {
            Log::warning("NewOnuDetectionService: getUnregisteredOnus failed for OLT {$olt->id} ({$olt->name}): " . $e->getMessage());
            return 0;
        }

        if (!is_array($unreg) || empty($unreg)) {
            // Refresh empty snapshot so a future re-appearance is treated as new
            Cache::put($this->cacheKey($olt), [], self::SEEN_TTL);
            return 0;
        }

        $seen = (array) Cache::get($this->cacheKey($olt), []);
        $currentSerials = [];
        $newCount = 0;

        foreach ($unreg as $u) {
            $sn = $u['serial_number'] ?? null;
            if (!$sn) continue;
            $currentSerials[] = $sn;

            if (in_array($sn, $seen, true)) continue; // already notified

            try {
                $popUser->notify(new NewOnuDetected($olt, $u));
                $newCount++;
            } catch (\Throwable $e) {
                Log::warning("NewOnuDetectionService: notify failed for SN {$sn}: " . $e->getMessage());
            }
        }

        Cache::put($this->cacheKey($olt), $currentSerials, self::SEEN_TTL);
        return $newCount;
    }

    /**
     * Reset the "seen" snapshot for an OLT (so the next scan re-reports everything).
     * Useful when a user manually registers all unreg ONUs and wants a clean slate.
     */
    public function forgetOlt(Olt $olt): void
    {
        Cache::forget($this->cacheKey($olt));
    }

    protected function cacheKey(Olt $olt): string
    {
        return "unreg_onu_seen:{$olt->id}";
    }
}
