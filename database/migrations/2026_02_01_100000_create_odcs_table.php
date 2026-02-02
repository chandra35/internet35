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
        Schema::create('odcs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pop_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('router_id')->nullable()->constrained('routers')->nullOnDelete();
            
            $table->string('name'); // ODC-001, ODC-DESA-A
            $table->string('code')->unique(); // Unique code for identification
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            
            // Location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Capacity
            $table->integer('total_ports')->default(8); // Total splitter ports (8, 16, 32)
            $table->integer('used_ports')->default(0); // Calculated from ODPs
            
            // Status
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            
            // Physical info
            $table->string('cabinet_type')->nullable(); // Wall mount, Pole mount, etc
            $table->string('cable_type')->nullable(); // G.652D, G.657A, etc
            $table->integer('cable_core')->nullable(); // 12, 24, 48, 96 core
            $table->decimal('cable_distance', 10, 2)->nullable(); // Distance from OLT in meters
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['pop_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odcs');
    }
};
