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
        Schema::create('pop_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique(); // One setting per admin-pop user
            
            // ISP Information
            $table->string('isp_name')->nullable();
            $table->string('isp_tagline')->nullable();
            $table->string('isp_logo')->nullable();
            $table->string('isp_logo_dark')->nullable(); // For dark theme
            $table->string('isp_favicon')->nullable();
            
            // POP Information
            $table->string('pop_name')->nullable();
            $table->string('pop_code', 20)->nullable();
            
            // Address
            $table->text('address')->nullable();
            $table->char('province_code', 2)->nullable();
            $table->char('city_code', 4)->nullable();
            $table->char('district_code', 7)->nullable();
            $table->char('village_code', 10)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('phone_secondary', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('email_billing')->nullable();
            $table->string('email_support')->nullable();
            $table->string('website')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('telegram')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            
            // Invoice Settings
            $table->string('invoice_prefix', 20)->default('INV-');
            $table->integer('invoice_due_days')->default(7);
            $table->text('invoice_notes')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->text('invoice_terms')->nullable();
            $table->string('invoice_bank_name')->nullable();
            $table->string('invoice_bank_account')->nullable();
            $table->string('invoice_bank_holder')->nullable();
            
            // Tax Settings
            $table->boolean('ppn_enabled')->default(false);
            $table->decimal('ppn_percentage', 5, 2)->default(11.00);
            $table->string('npwp', 30)->nullable();
            $table->string('npwp_name')->nullable();
            $table->text('npwp_address')->nullable();
            
            // Business Info
            $table->string('business_type')->nullable(); // PT, CV, UD, Perorangan
            $table->string('business_permit_number')->nullable(); // NIB/SIUP
            $table->date('business_permit_date')->nullable();
            
            $table->timestamps();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pop_settings');
    }
};
