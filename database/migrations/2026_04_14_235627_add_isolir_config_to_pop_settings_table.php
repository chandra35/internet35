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
            $table->string('isolir_pool_name', 50)->default('pool-isolir')->after('isolir_profile_name');
            $table->string('isolir_pool_range', 100)->default('10.99.0.2-10.99.0.254')->after('isolir_pool_name');
            $table->string('isolir_local_address', 45)->default('10.99.0.1')->after('isolir_pool_range');
            $table->string('isolir_dns_server', 45)->nullable()->after('isolir_local_address');
            $table->string('isolir_rate_limit', 50)->default('128k/128k')->after('isolir_dns_server');
            $table->string('isolir_redirect_url')->nullable()->after('isolir_rate_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->dropColumn([
                'isolir_pool_name',
                'isolir_pool_range',
                'isolir_local_address',
                'isolir_dns_server',
                'isolir_rate_limit',
                'isolir_redirect_url',
            ]);
        });
    }
};
