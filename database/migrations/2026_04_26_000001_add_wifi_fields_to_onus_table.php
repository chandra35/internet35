<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan konfigurasi WiFi per-ONU supaya bisa di-push otomatis
 * saat factory reset via GenieACS auto-provisioning.
 *
 * wifi_password dienkripsi di application layer (cast 'encrypted').
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->string('wifi_ssid', 64)->nullable()->after('webui_password');
            $table->text('wifi_password')->nullable()->after('wifi_ssid');
        });
    }

    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->dropColumn(['wifi_ssid', 'wifi_password']);
        });
    }
};
