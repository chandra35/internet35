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
        Schema::table('customers', function (Blueprint $table) {
            // Add ODP relation after router_id
            $table->foreignUuid('odp_id')->nullable()->after('router_id')->constrained('odps')->nullOnDelete();
            $table->integer('odp_port')->nullable()->after('odp_id'); // Which port on ODP
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['odp_id']);
            $table->dropColumn(['odp_id', 'odp_port']);
        });
    }
};
