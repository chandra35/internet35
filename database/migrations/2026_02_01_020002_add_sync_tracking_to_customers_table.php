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
            if (!Schema::hasColumn('customers', 'mikrotik_synced')) {
                $table->boolean('mikrotik_synced')->default(false)->after('internal_notes');
            }
            if (!Schema::hasColumn('customers', 'mikrotik_synced_at')) {
                $table->timestamp('mikrotik_synced_at')->nullable()->after('mikrotik_synced');
            }
            if (!Schema::hasColumn('customers', 'radius_synced')) {
                $table->boolean('radius_synced')->default(false)->after('mikrotik_synced_at');
            }
            if (!Schema::hasColumn('customers', 'radius_synced_at')) {
                $table->timestamp('radius_synced_at')->nullable()->after('radius_synced');
            }
            if (!Schema::hasColumn('customers', 'last_sync_error')) {
                $table->text('last_sync_error')->nullable()->after('radius_synced_at');
            }
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
