<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_cards', function (Blueprint $table) {
            $table->json('vlan_config')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('olt_cards', function (Blueprint $table) {
            $table->dropColumn('vlan_config');
        });
    }
};
