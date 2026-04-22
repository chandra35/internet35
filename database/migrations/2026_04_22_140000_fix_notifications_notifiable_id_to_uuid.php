<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The previous migration `2026_04_22_130000_create_notifications_table` used
 * `morphs('notifiable')` which created `notifiable_id` as BIGINT UNSIGNED.
 * Our `users` table uses UUID primary keys, so any inserts truncated the data
 * (MySQL warning 1265) and notifications were never persisted.
 *
 * This migration recreates the column as CHAR(36) UUID with the proper composite
 * index that `uuidMorphs()` would have created.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the broken composite index first (created by morphs())
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
        });

        // Change the column type to CHAR(36) for UUID
        DB::statement('ALTER TABLE notifications MODIFY notifiable_id CHAR(36) NOT NULL');

        // Recreate the composite index
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
        });
        DB::statement('ALTER TABLE notifications MODIFY notifiable_id BIGINT UNSIGNED NOT NULL');
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }
};
