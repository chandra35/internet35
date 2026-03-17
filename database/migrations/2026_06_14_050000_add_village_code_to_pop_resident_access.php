<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pop_resident_access', function (Blueprint $table) {
            if (!Schema::hasColumn('pop_resident_access', 'village_code')) {
                $table->string('village_code', 10)->nullable()->after('pop_id');
            }
        });

        // Separate statement: drop FK, drop unique, re-add FK, add composite unique
        Schema::table('pop_resident_access', function (Blueprint $table) {
            $table->dropForeign(['pop_id']);
        });

        Schema::table('pop_resident_access', function (Blueprint $table) {
            $table->dropUnique(['pop_id']);
        });

        Schema::table('pop_resident_access', function (Blueprint $table) {
            $table->foreign('pop_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['pop_id', 'village_code'], 'pop_village_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pop_resident_access', function (Blueprint $table) {
            $table->dropUnique('pop_village_unique');
            $table->dropForeign(['pop_id']);
            $table->dropColumn('village_code');
            $table->unique('pop_id');
            $table->foreign('pop_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
