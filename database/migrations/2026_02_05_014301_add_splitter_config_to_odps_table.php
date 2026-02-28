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
            // Splitter configuration type: equal (1:8, 1:16) or cascade (unequal + equal)
            $table->enum('splitter_config_type', ['equal', 'cascade'])->default('equal')
                ->after('splitter_type')
                ->comment('equal = standard splitter, cascade = unequal ratio + branch splitter');
            
            // For cascade/unequal configuration
            $table->string('unequal_ratio', 10)->nullable()
                ->after('splitter_config_type')
                ->comment('Unequal splitter ratio: 90:10, 80:20, 70:30');
            
            // Branch splitter for cascade (splitter after unequal)
            $table->string('branch_splitter', 10)->nullable()
                ->after('unequal_ratio')
                ->comment('Branch splitter ratio: 1:4, 1:8, 1:16');
            
            // Calculated total loss breakdown
            $table->decimal('fiber_loss', 5, 2)->nullable()
                ->after('branch_splitter')
                ->comment('Calculated fiber loss in dB (distance * loss_per_km)');
            
            $table->decimal('unequal_loss', 5, 2)->nullable()
                ->after('fiber_loss')
                ->comment('Loss from unequal splitter in dB');
            
            $table->decimal('branch_loss', 5, 2)->nullable()
                ->after('unequal_loss')
                ->comment('Loss from branch splitter in dB');
            
            // Total attenuation from source to customer
            $table->decimal('total_loss', 6, 2)->nullable()
                ->after('branch_loss')
                ->comment('Total attenuation from source in dB');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn([
                'splitter_config_type',
                'unequal_ratio',
                'branch_splitter',
                'fiber_loss',
                'unequal_loss',
                'branch_loss',
                'total_loss',
            ]);
        });
    }
};
