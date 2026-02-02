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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Owner (admin-pop)
            
            // Gateway Info
            $table->string('gateway_type', 50); // midtrans, duitku, xendit, tripay, ipaymu
            $table->string('gateway_name')->nullable(); // Custom name
            $table->boolean('is_active')->default(false);
            $table->boolean('is_sandbox')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Credentials (encrypted JSON)
            $table->text('credentials')->nullable();
            
            // URLs
            $table->string('webhook_url')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('return_url')->nullable();
            $table->string('cancel_url')->nullable();
            
            // Fee Settings
            $table->boolean('fee_paid_by_customer')->default(true);
            $table->decimal('additional_fee', 10, 2)->default(0);
            $table->decimal('additional_fee_percentage', 5, 2)->default(0);
            
            // Sandbox Verification (untuk Duitku, dll)
            $table->enum('sandbox_status', [
                'not_required',  // Gateway tidak perlu verifikasi
                'not_submitted', // Belum mengajukan
                'pending',       // Menunggu review
                'in_review',     // Sedang direview
                'approved',      // Disetujui
                'rejected',      // Ditolak
            ])->default('not_required');
            $table->timestamp('sandbox_request_date')->nullable();
            $table->timestamp('sandbox_approved_date')->nullable();
            $table->text('sandbox_rejection_reason')->nullable();
            $table->uuid('sandbox_reviewed_by')->nullable();
            
            // Verification Documents (JSON)
            $table->json('verification_documents')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Production Request
            $table->enum('production_status', [
                'not_ready',
                'pending',
                'approved',
                'rejected',
            ])->default('not_ready');
            $table->timestamp('production_request_date')->nullable();
            $table->timestamp('production_approved_date')->nullable();
            
            // Statistics
            $table->unsignedInteger('total_transactions')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('last_transaction_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
                
            $table->foreign('sandbox_reviewed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            $table->unique(['user_id', 'gateway_type']);
            $table->index(['gateway_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
