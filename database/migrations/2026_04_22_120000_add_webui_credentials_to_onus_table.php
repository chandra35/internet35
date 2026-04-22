<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-ONU WebUI credentials override.
 *
 * For brands where TR-069 cannot reliably configure WAN VLAN/PPPoE
 * (e.g. Fiberhome HG6145F), we scrape the ONU's admin WebUI instead.
 * Most ISPs use a single password (kept in .env), but some ONUs ship
 * with custom credentials — these columns let an operator override
 * the default per-ONU.
 *
 * `webui_password` is encrypted at the application layer
 * (Onu model uses 'encrypted' cast).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->string('webui_user', 64)->nullable()->after('pppoe_username');
            $table->text('webui_password')->nullable()->after('webui_user');
        });
    }

    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->dropColumn(['webui_user', 'webui_password']);
        });
    }
};
