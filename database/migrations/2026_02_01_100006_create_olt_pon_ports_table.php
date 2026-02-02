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
        Schema::create('olt_pon_ports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olt_id')->constrained('olts')->cascadeOnDelete();
            
            $table->integer('slot')->default(0); // Slot number (0 for single slot OLT)
            $table->integer('port'); // PON port number (1-16)
            $table->string('name')->nullable(); // Custom name
            
            // Capacity
            $table->integer('max_onu')->default(128);
            $table->integer('registered_onu')->default(0);
            $table->integer('online_onu')->default(0);
            
            // Status from OLT
            $table->enum('status', ['up', 'down', 'testing', 'unknown'])->default('unknown');
            $table->enum('admin_status', ['enabled', 'disabled'])->default('enabled');
            
            // Optical info
            $table->decimal('tx_power', 8, 2)->nullable(); // dBm
            $table->decimal('rx_power_min', 8, 2)->nullable(); // Minimum ONU Rx Power
            $table->decimal('rx_power_max', 8, 2)->nullable(); // Maximum ONU Rx Power
            $table->decimal('rx_power_avg', 8, 2)->nullable(); // Average ONU Rx Power
            
            // Traffic stats
            $table->bigInteger('in_octets')->default(0);
            $table->bigInteger('out_octets')->default(0);
            $table->bigInteger('in_errors')->default(0);
            $table->bigInteger('out_errors')->default(0);
            
            $table->text('description')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            $table->unique(['olt_id', 'slot', 'port']);
            $table->index(['olt_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olt_pon_ports');
    }
};
