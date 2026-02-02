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
        // Add OLT reference to ODC
        Schema::table('odcs', function (Blueprint $table) {
            $table->foreignUuid('olt_id')->nullable()->after('router_id')->constrained('olts')->nullOnDelete();
            $table->integer('olt_pon_port')->nullable()->after('olt_id'); // Which PON port on OLT
            $table->integer('olt_slot')->nullable()->after('olt_pon_port'); // Slot number
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odcs', function (Blueprint $table) {
            $table->dropForeign(['olt_id']);
            $table->dropColumn(['olt_id', 'olt_pon_port', 'olt_slot']);
        });
    }
};
