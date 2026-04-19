<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'tcont' to the type enum
        DB::statement("ALTER TABLE olt_profiles MODIFY COLUMN type ENUM('line','service','traffic','tcont') NOT NULL DEFAULT 'line'");
    }

    public function down(): void
    {
        // Remove rows with type tcont first, then revert enum
        DB::table('olt_profiles')->where('type', 'tcont')->delete();
        DB::statement("ALTER TABLE olt_profiles MODIFY COLUMN type ENUM('line','service','traffic') NOT NULL DEFAULT 'line'");
    }
};
