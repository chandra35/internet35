<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ppp_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('router_id');
            $table->uuid('pop_id')->nullable(); // POP owner
            
            // Mikrotik Profile Data
            $table->string('mikrotik_id')->nullable(); // .id from Mikrotik
            $table->string('name'); // Profile name
            $table->string('local_address')->nullable(); // Local Address
            $table->string('remote_address')->nullable(); // Remote Address (IP Pool name)
            $table->string('bridge')->nullable();
            
            // Rate Limits
            $table->string('rate_limit')->nullable(); // rx/tx format e.g. "10M/10M"
            $table->string('incoming_filter')->nullable();
            $table->string('outgoing_filter')->nullable();
            $table->string('address_list')->nullable();
            
            // Session settings
            $table->string('session_timeout')->nullable();
            $table->string('idle_timeout')->nullable();
            $table->string('keepalive_timeout')->nullable();
            
            // DNS
            $table->string('dns_server')->nullable();
            $table->string('wins_server')->nullable();
            
            // Queue
            $table->string('parent_queue')->nullable();
            $table->string('queue_type')->nullable();
            
            // Other settings
            $table->string('change_tcp_mss')->nullable();
            $table->string('use_upnp')->nullable();
            $table->string('use_mpls')->nullable();
            $table->string('use_compression')->nullable();
            $table->string('use_encryption')->nullable();
            $table->string('only_one')->nullable();
            
            $table->text('comment')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_synced')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('router_id')->references('id')->on('routers')->onDelete('cascade');
            $table->foreign('pop_id')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['router_id', 'name']);
            $table->index(['pop_id', 'router_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppp_profiles');
    }
};
