<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->boolean('reminder_enabled')->default(true)->after('invoice_terms')
                  ->comment('Enable/disable billing reminder notifications for this POP');
        });
    }

    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->dropColumn('reminder_enabled');
        });
    }
};
