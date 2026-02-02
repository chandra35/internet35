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
        Schema::table('customers', function (Blueprint $table) {
            // Mikrotik sync tracking
            $table->boolean('mikrotik_synced')->default(false)->after('internal_notes');
            $table->timestamp('mikrotik_synced_at')->nullable()->after('mikrotik_synced');
            
            // Radius sync tracking
            $table->boolean('radius_synced')->default(false)->after('mikrotik_synced_at');
            $table->timestamp('radius_synced_at')->nullable()->after('radius_synced');
            
            // Sync error tracking
            $table->text('last_sync_error')->nullable()->after('radius_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'mikrotik_synced',
                'mikrotik_synced_at',
                'radius_synced',
                'radius_synced_at',
                'last_sync_error',
            ]);
        });
    }
};
