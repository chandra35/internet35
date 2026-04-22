<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add per-POP settings for in-app "New ONU detected" notifications,
     * plus a throttle timestamp so the cron can scan each POP at its own pace.
     */
    public function up(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            // JSON blob: { enabled, scan_interval, poll_interval, toast, sound, olts: [uuid,...] }
            $table->json('unreg_notif_settings')->nullable()->after('reminder_enabled');
            $table->timestamp('last_unreg_notif_scan_at')->nullable()->after('unreg_notif_settings');
        });
    }

    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->dropColumn(['unreg_notif_settings', 'last_unreg_notif_scan_at']);
        });
    }
};
