<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('data_status', 20)->default('valid')->after('kelurahan');
            $table->text('data_notes')->nullable()->after('data_status');
        });

        // Change NIK from unique to regular index so bad/empty NIK rows can still be imported
        Schema::table('residents', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->string('nik', 16)->nullable()->change();
            $table->index('nik');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropIndex(['nik']);
            $table->string('nik', 16)->unique()->change();
            $table->dropColumn(['data_status', 'data_notes']);
        });
    }
};
