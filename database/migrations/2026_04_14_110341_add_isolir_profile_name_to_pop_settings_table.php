<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->string('isolir_profile_name', 50)->default('isolir')->after('mikrotik_auto_sync');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->dropColumn('isolir_profile_name');
        });
    }
};
