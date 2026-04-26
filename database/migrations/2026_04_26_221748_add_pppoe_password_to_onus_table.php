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
        Schema::table('onus', function (Blueprint $table) {
            // Encrypted PPPoE password for standalone ONUs (no customer linked).
            // Provision API uses customer.pppoe_password first, falls back to this.
            $table->text('pppoe_password')->nullable()->after('pppoe_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->dropColumn('pppoe_password');
        });
    }
};
