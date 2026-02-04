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
        // Add photos to OLTs
        Schema::table('olts', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('notes');
        });

        // Add photos to ODCs
        Schema::table('odcs', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('notes');
        });

        // Add photos to ODPs
        Schema::table('odps', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olts', function (Blueprint $table) {
            $table->dropColumn('photos');
        });

        Schema::table('odcs', function (Blueprint $table) {
            $table->dropColumn('photos');
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
