<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->enum('odp_type', ['gpon', 'epon', 'xgpon', 'xgspon'])
                  ->default('gpon')
                  ->after('status')
                  ->comment('Jenis teknologi PON: GPON, EPON, XG-PON, XGS-PON');
        });
    }

    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn('odp_type');
        });
    }
};
