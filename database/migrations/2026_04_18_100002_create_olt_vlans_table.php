<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_vlans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olt_id')->constrained('olts')->cascadeOnDelete();

            $table->unsignedSmallInteger('vlan_id'); // 1-4095
            $table->string('name', 50)->nullable();  // VLAN0111, VLAN0335, etc.

            $table->enum('type', [
                'service',     // Internet/WAN traffic (PPPoE, IPoE)
                'management',  // TR069/CWMP remote management
                'voip',        // Voice over IP
                'iptv',        // IPTV multicast
                'infra',       // Infrastructure/internal
                'other',
            ])->default('other');

            $table->text('description')->nullable();

            // Uplink trunk info — which uplink interfaces carry this VLAN
            $table->json('uplink_ports')->nullable(); // ["xgei_1/3/2", "gei_1/3/1"]

            $table->boolean('is_synced')->default(false); // Synced from OLT?
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['olt_id', 'vlan_id']);
            $table->index(['olt_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_vlans');
    }
};
