<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('invoice_generate_days_before_due')
                ->default(3)
                ->after('invoice_due_days');
            $table->unsignedTinyInteger('auto_isolir_grace_days')
                ->default(0)
                ->after('invoice_generate_days_before_due');
            $table->time('auto_isolir_time')
                ->default('20:00:00')
                ->after('auto_isolir_grace_days');
        });
    }

    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_generate_days_before_due',
                'auto_isolir_grace_days',
                'auto_isolir_time',
            ]);
        });
    }
};
