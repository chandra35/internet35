<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create zones table
        Schema::create('zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olt_id')->nullable()->constrained('olts')->nullOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['olt_id', 'name']);
        });

        // Add zone_id to odps table
        Schema::table('odps', function (Blueprint $table) {
            $table->foreignUuid('zone_id')->nullable()->after('olt_slot')->constrained('zones')->nullOnDelete();
        });

        // Add zone_id to onus table
        Schema::table('onus', function (Blueprint $table) {
            $table->foreignUuid('zone_id')->nullable()->after('odp_id')->constrained('zones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_id');
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_id');
        });

        Schema::dropIfExists('zones');
    }
};
