<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('province_code', 10)->nullable()->after('kelurahan');
            $table->string('city_code', 10)->nullable()->after('province_code');
            $table->string('district_code', 10)->nullable()->after('city_code');
            $table->string('village_code', 10)->nullable()->after('district_code');

            $table->index('village_code');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropIndex(['village_code']);
            $table->dropColumn(['province_code', 'city_code', 'district_code', 'village_code']);
        });
    }
};
