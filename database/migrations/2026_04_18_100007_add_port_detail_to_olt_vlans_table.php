<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_vlans', function (Blueprint $table) {
            $table->json('tagged_ports')->nullable()->after('uplink_ports');
            $table->json('untagged_ports')->nullable()->after('tagged_ports');
            $table->string('multicast_mode', 30)->nullable()->after('untagged_ports');
        });
    }

    public function down(): void
    {
        Schema::table('olt_vlans', function (Blueprint $table) {
            $table->dropColumn(['tagged_ports', 'untagged_ports', 'multicast_mode']);
        });
    }
};
