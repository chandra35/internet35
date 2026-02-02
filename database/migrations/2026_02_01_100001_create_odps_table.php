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
        Schema::create('odps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pop_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('odc_id')->constrained('odcs')->cascadeOnDelete();
            
            $table->string('name'); // ODP-001, ODP-RT01-RW02
            $table->string('code')->unique(); // Unique code for identification
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            
            // Location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Capacity
            $table->integer('total_ports')->default(8); // Splitter ports (4, 8, 16)
            $table->integer('used_ports')->default(0); // Calculated from customers
            
            // Port mapping to ODC
            $table->integer('odc_port')->nullable(); // Which port on ODC this ODP connects to
            
            // Status
            $table->enum('status', ['active', 'inactive', 'maintenance', 'full'])->default('active');
            
            // Physical info
            $table->string('box_type')->nullable(); // Wall mount, Pole mount, Underground
            $table->string('splitter_type')->nullable(); // 1:4, 1:8, 1:16
            $table->decimal('cable_distance', 10, 2)->nullable(); // Distance from ODC in meters
            
            // Installation info
            $table->string('pole_number')->nullable(); // Nomor tiang PLN/Telkom
            $table->date('installation_date')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['pop_id', 'odc_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odps');
    }
};
