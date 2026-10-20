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
        Schema::table('pop_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('pop_settings', 'pop_prefix')) {
                $table->string('pop_prefix', 10)->nullable()->after('pop_code');
            }
            if (!Schema::hasColumn('pop_settings', 'mikrotik_sync_enabled')) {
                $table->boolean('mikrotik_sync_enabled')->default(false)->after('pop_prefix');
            }
            if (!Schema::hasColumn('pop_settings', 'mikrotik_auto_sync')) {
                $table->boolean('mikrotik_auto_sync')->default(false)->after('mikrotik_sync_enabled');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_enabled')) {
                $table->boolean('radius_enabled')->default(false)->after('mikrotik_auto_sync');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_host')) {
                $table->string('radius_host')->nullable()->after('radius_enabled');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_port')) {
                $table->integer('radius_port')->default(3306)->after('radius_host');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_database')) {
                $table->string('radius_database')->default('radius')->after('radius_port');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_username')) {
                $table->string('radius_username')->nullable()->after('radius_database');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_password')) {
                $table->string('radius_password')->nullable()->after('radius_username');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_nas_ip')) {
                $table->string('radius_nas_ip')->nullable()->after('radius_password');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_nas_secret')) {
                $table->string('radius_nas_secret')->nullable()->after('radius_nas_ip');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_coa_port')) {
                $table->integer('radius_coa_port')->default(3799)->after('radius_nas_secret');
            }
            if (!Schema::hasColumn('pop_settings', 'radius_auto_sync')) {
                $table->boolean('radius_auto_sync')->default(false)->after('radius_coa_port');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->dropColumn([
                'pop_prefix',
                'mikrotik_sync_enabled',
                'mikrotik_auto_sync',
                'radius_enabled',
                'radius_host',
                'radius_port',
                'radius_database',
                'radius_username',
                'radius_password',
                'radius_nas_ip',
                'radius_nas_secret',
                'radius_coa_port',
                'radius_auto_sync',
            ]);
        });
    }
};
