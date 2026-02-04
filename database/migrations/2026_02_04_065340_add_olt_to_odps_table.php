<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add OLT fields to ODP for direct OLT->ODP connection (without ODC)
     * This supports simplified FTTH topology: OLT -> ODP -> Customer
     */
    public function up(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            // Make odc_id nullable for direct OLT connection
            $table->foreignUuid('odc_id')->nullable()->change();
            
            // Add OLT connection fields (for direct connection without ODC)
            $table->foreignUuid('olt_id')->nullable()->after('odc_id')->constrained('olts')->nullOnDelete();
            $table->integer('olt_pon_port')->nullable()->after('olt_id'); // Which PON port on OLT
            $table->integer('olt_slot')->nullable()->after('olt_pon_port'); // Slot number
            
            // Add splitter info for relay/cascade topology
            $table->integer('splitter_level')->default(1)->after('splitter_type'); // 1 = first level, 2 = second level
            $table->foreignUuid('parent_odp_id')->nullable()->after('splitter_level')
                ->constrained('odps')->nullOnDelete(); // For cascade splitter (ODP -> ODP)
            
            // Add indexes
            $table->index(['olt_id', 'olt_pon_port']);
            $table->index('parent_odp_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropForeign(['olt_id']);
            $table->dropForeign(['parent_odp_id']);
            $table->dropIndex(['olt_id', 'olt_pon_port']);
            $table->dropIndex(['parent_odp_id']);
            $table->dropColumn(['olt_id', 'olt_pon_port', 'olt_slot', 'splitter_level', 'parent_odp_id']);
        });
    }
};
