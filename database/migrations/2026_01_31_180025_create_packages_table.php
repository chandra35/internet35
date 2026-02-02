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
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('router_id');
            
            // Package info
            $table->string('name'); // Display name in app
            $table->string('mikrotik_profile_name'); // Profile name in Mikrotik
            $table->string('mikrotik_profile_id')->nullable(); // .id from Mikrotik
            
            // PPP Profile settings
            $table->string('rate_limit')->nullable(); // e.g., "10M/10M" or "10M/10M 5M/5M 10M/10M 10/10"
            $table->string('local_address')->nullable(); // IP or pool name
            $table->string('remote_address')->nullable(); // IP or pool name
            $table->string('parent_queue')->nullable();
            $table->string('address_list')->nullable();
            $table->string('dns_server')->nullable();
            $table->integer('session_timeout')->nullable(); // in seconds
            $table->integer('idle_timeout')->nullable(); // in seconds
            $table->string('incoming_filter')->nullable();
            $table->string('outgoing_filter')->nullable();
            $table->string('bridge')->nullable();
            $table->string('interface_list')->nullable();
            $table->boolean('only_one')->default(false);
            $table->text('mikrotik_comment')->nullable(); // Comment in Mikrotik
            
            // App-specific fields
            $table->decimal('price', 12, 2)->default(0); // Monthly price
            $table->integer('validity_days')->default(30); // Package validity
            $table->integer('speed_up')->nullable(); // Upload speed in Kbps for display
            $table->integer('speed_down')->nullable(); // Download speed in Kbps for display
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true); // Show in customer portal
            
            // Sync tracking
            $table->enum('sync_status', ['synced', 'pending_push', 'pending_pull', 'conflict', 'local_only', 'mikrotik_only'])->default('local_only');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('mikrotik_data')->nullable(); // Raw data from Mikrotik for diff
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('router_id')->references('id')->on('routers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            
            // Unique constraint: one profile name per router
            $table->unique(['router_id', 'mikrotik_profile_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
