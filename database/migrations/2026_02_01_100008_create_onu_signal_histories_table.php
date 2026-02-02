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
        Schema::create('onu_signal_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('onu_id')->constrained('onus')->cascadeOnDelete();
            $table->foreignUuid('olt_id')->constrained('olts')->cascadeOnDelete();
            
            // Signal metrics
            $table->decimal('rx_power', 8, 2)->nullable(); // ONU Rx (dBm)
            $table->decimal('tx_power', 8, 2)->nullable(); // ONU Tx (dBm)
            $table->decimal('olt_rx_power', 8, 2)->nullable(); // OLT Rx from ONU (dBm)
            $table->decimal('temperature', 8, 2)->nullable();
            $table->decimal('voltage', 8, 2)->nullable();
            $table->decimal('bias_current', 8, 2)->nullable();
            
            // Status at time of record
            $table->string('status', 20)->nullable();
            $table->decimal('distance', 10, 2)->nullable();
            
            $table->timestamp('recorded_at')->useCurrent();
            
            $table->index(['onu_id', 'recorded_at']);
            $table->index(['olt_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onu_signal_histories');
    }
};
