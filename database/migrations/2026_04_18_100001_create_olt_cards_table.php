<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olt_id')->constrained('olts')->cascadeOnDelete();

            $table->unsignedTinyInteger('rack')->default(1);
            $table->unsignedTinyInteger('shelf')->default(1);
            $table->unsignedTinyInteger('slot');

            $table->string('configured_type', 20)->nullable(); // GTGH, SMXA, etc.
            $table->string('real_type', 20)->nullable();       // GTGHK, SMXA, etc.
            $table->unsignedSmallInteger('port_count')->default(0);
            $table->string('hardware_version', 20)->nullable();
            $table->string('software_version', 20)->nullable();

            $table->enum('status', ['inservice', 'offline', 'standby', 'failed', 'unknown'])->default('unknown');
            $table->enum('role', ['gpon', 'epon', 'uplink', 'management', 'power', 'fan', 'other'])->default('other');

            $table->text('description')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['olt_id', 'rack', 'shelf', 'slot']);
            $table->index(['olt_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_cards');
    }
};
