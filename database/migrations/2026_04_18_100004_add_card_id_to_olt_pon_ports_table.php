<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_pon_ports', function (Blueprint $table) {
            $table->foreignUuid('card_id')->nullable()->after('olt_id')
                  ->constrained('olt_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('olt_pon_ports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('card_id');
        });
    }
};
