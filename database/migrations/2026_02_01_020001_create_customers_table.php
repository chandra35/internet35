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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable(); // Link to users table for login
            $table->uuid('pop_id'); // POP owner (admin-pop user_id)
            
            // Customer Info
            $table->string('customer_id')->unique(); // Customer ID (e.g., POP-001)
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->string('nik', 16)->nullable(); // NIK KTP
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            
            // Address
            $table->text('address');
            $table->string('province_code')->nullable();
            $table->string('city_code')->nullable();
            $table->string('district_code')->nullable();
            $table->string('village_code')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Photos
            $table->string('photo_ktp')->nullable(); // KTP photo
            $table->string('photo_selfie')->nullable(); // Selfie photo
            $table->string('photo_house')->nullable(); // House photo (optional)
            
            // PPPoE/Hotspot Secret
            $table->uuid('router_id')->nullable();
            $table->uuid('package_id')->nullable();
            $table->string('pppoe_username')->nullable();
            $table->text('pppoe_password')->nullable(); // encrypted
            $table->string('mikrotik_secret_id')->nullable(); // .id from Mikrotik
            $table->string('service_type')->default('pppoe'); // pppoe, hotspot, static
            $table->string('remote_address')->nullable(); // IP assigned
            $table->string('mac_address')->nullable();
            $table->string('caller_id')->nullable();
            $table->text('mikrotik_comment')->nullable();
            
            // Subscription
            $table->date('installation_date')->nullable();
            $table->date('active_until')->nullable(); // Next billing date
            $table->date('due_date')->nullable(); // Payment due date
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->decimal('installation_fee', 12, 2)->default(0);
            $table->integer('billing_day')->default(1); // 1-28
            $table->integer('grace_period_days')->default(3); // Days after due date before suspend
            
            // Status
            $table->enum('status', ['pending', 'active', 'suspended', 'terminated', 'expired'])->default('pending');
            $table->enum('mikrotik_status', ['enabled', 'disabled', 'not_synced'])->default('not_synced');
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspend_reason')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->text('terminate_reason')->nullable();
            
            // Sync tracking
            $table->enum('sync_status', ['synced', 'pending_push', 'pending_pull', 'conflict', 'local_only', 'mikrotik_only'])->default('local_only');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('mikrotik_data')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable(); // Admin only
            
            // Tracking
            $table->uuid('registered_by')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('pop_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('router_id')->references('id')->on('routers')->nullOnDelete();
            $table->foreign('package_id')->references('id')->on('packages')->nullOnDelete();
            $table->foreign('registered_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index('pop_id');
            $table->index('router_id');
            $table->index('package_id');
            $table->index('status');
            $table->index('pppoe_username');
            $table->index('phone');
            $table->index('email');
            $table->index('nik');
        });

        // Create customer invoices table
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('pop_id');
            
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('period_start');
            $table->date('period_end');
            
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            
            $table->enum('status', ['draft', 'pending', 'paid', 'partial', 'overdue', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('items')->nullable(); // Invoice line items
            
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('pop_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['pop_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('due_date');
        });

        // Create customer payments table
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('invoice_id')->nullable();
            $table->uuid('pop_id');
            $table->uuid('payment_gateway_id')->nullable();
            
            $table->string('payment_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method'); // manual, midtrans, xendit, etc
            $table->string('payment_channel')->nullable(); // bank_transfer, qris, va, etc
            
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'expired', 'refunded'])->default('pending');
            
            // Payment gateway response
            $table->string('external_id')->nullable(); // Transaction ID from gateway
            $table->string('external_reference')->nullable();
            $table->json('gateway_response')->nullable();
            $table->string('payment_url')->nullable();
            $table->timestamp('expired_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('customer_invoices')->nullOnDelete();
            $table->foreign('pop_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('payment_gateway_id')->references('id')->on('payment_gateways')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['pop_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('customer_invoices');
        Schema::dropIfExists('customers');
    }
};
