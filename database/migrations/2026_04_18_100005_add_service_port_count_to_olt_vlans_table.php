<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_vlans', function (Blueprint $table) {
            $table->unsignedInteger('service_port_count')->default(0)->after('uplink_ports');
        });
    }

    public function down(): void
    {
        Schema::table('olt_vlans', function (Blueprint $table) {
            $table->dropColumn('service_port_count');
        });
    }
};
