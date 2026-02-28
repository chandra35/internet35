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
        Schema::table('odps', function (Blueprint $table) {
            // Input power source
            $table->decimal('input_power', 6, 2)->nullable()->after('splitter_type')
                ->comment('Input optical power in dBm (from OLT/ODC/parent ODP)');
            
            // Fiber information
            $table->decimal('fiber_distance', 8, 3)->nullable()->after('input_power')
                ->comment('Fiber distance from parent in km');
            $table->decimal('fiber_loss_per_km', 4, 2)->default(0.35)->after('fiber_distance')
                ->comment('Fiber attenuation in dB/km (default 0.35 for G.652)');
            
            // Splitter configuration
            $table->string('splitter_ratio', 20)->nullable()->after('fiber_loss_per_km')
                ->comment('Splitter ratio: 1:2, 1:4, 1:8, 1:16, 1:32 or unequal like 90:10, 85:15');
            $table->decimal('splitter_loss', 5, 2)->nullable()->after('splitter_ratio')
                ->comment('Total splitter insertion loss in dB');
            
            // Calculated output power
            $table->decimal('output_power', 6, 2)->nullable()->after('splitter_loss')
                ->comment('Calculated output power per port in dBm');
            
            // For cascade/relay with unequal splitter
            $table->decimal('cascade_output_power', 6, 2)->nullable()->after('output_power')
                ->comment('Power output for cascade port (for unequal splitter)');
            
            // Power source tracking
            $table->boolean('is_power_manual')->default(false)->after('cascade_output_power')
                ->comment('True if input power was manually entered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn([
                'input_power',
                'fiber_distance',
                'fiber_loss_per_km',
                'splitter_ratio',
                'splitter_loss',
                'output_power',
                'cascade_output_power',
                'is_power_manual',
            ]);
        });
    }
};
