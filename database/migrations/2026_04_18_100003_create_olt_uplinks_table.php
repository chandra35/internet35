<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_uplinks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->foreignUuid('card_id')->nullable()->constrained('olt_cards')->nullOnDelete();

            $table->string('interface_name', 30); // xgei_1/3/2, gei_1/3/1
            $table->enum('interface_type', ['gei', 'xgei', 'other'])->default('gei');

            $table->unsignedTinyInteger('rack')->default(1);
            $table->unsignedTinyInteger('shelf')->default(1);
            $table->unsignedTinyInteger('slot');
            $table->unsignedTinyInteger('port');

            $table->string('switchport_mode', 20)->nullable(); // hybrid, trunk, access
            $table->json('tagged_vlans')->nullable();           // [111, 335, 338]
            $table->unsignedSmallInteger('native_vlan')->nullable();

            $table->enum('status', ['up', 'down', 'unknown'])->default('unknown');
            $table->enum('admin_status', ['enabled', 'disabled'])->default('enabled');

            // Traffic stats
            $table->bigInteger('in_octets')->default(0);
            $table->bigInteger('out_octets')->default(0);
            $table->bigInteger('in_rate_bps')->default(0);
            $table->bigInteger('out_rate_bps')->default(0);

            $table->text('description')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['olt_id', 'interface_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_uplinks');
    }
};
