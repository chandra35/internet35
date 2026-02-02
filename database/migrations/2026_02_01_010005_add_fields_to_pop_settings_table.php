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
            // Add bank_accounts as JSON
            $table->json('bank_accounts')->nullable()->after('invoice_terms');
            
            // Add PPN settings
            $table->string('ppn_method', 20)->default('exclusive')->after('ppn_percentage');
            $table->string('ppn_display', 20)->default('separate')->after('ppn_method');
            
            // Add Business Info
            $table->string('business_name')->nullable()->after('npwp_address');
            $table->string('nib', 30)->nullable()->after('business_type');
            $table->string('isp_license_number')->nullable()->after('nib');
            
            // Drop old bank columns
            $table->dropColumn(['invoice_bank_name', 'invoice_bank_account', 'invoice_bank_holder']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->dropColumn(['bank_accounts', 'ppn_method', 'ppn_display', 'business_name', 'nib', 'isp_license_number']);
            
            $table->string('invoice_bank_name')->nullable();
            $table->string('invoice_bank_account')->nullable();
            $table->string('invoice_bank_holder')->nullable();
        });
    }
};
