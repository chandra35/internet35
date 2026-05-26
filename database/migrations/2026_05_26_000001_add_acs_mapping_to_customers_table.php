<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('acs_device_id')->nullable()->after('caller_id');
            $table->string('acs_serial_number')->nullable()->after('acs_device_id');
            $table->timestamp('acs_last_matched_at')->nullable()->after('acs_serial_number');
            $table->json('acs_metadata')->nullable()->after('acs_last_matched_at');

            $table->index('acs_device_id');
            $table->index('acs_serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['acs_device_id']);
            $table->dropIndex(['acs_serial_number']);
            $table->dropColumn([
                'acs_device_id',
                'acs_serial_number',
                'acs_last_matched_at',
                'acs_metadata',
            ]);
        });
    }
};
