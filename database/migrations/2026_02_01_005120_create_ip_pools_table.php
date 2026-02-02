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
        Schema::create('ip_pools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('router_id');
            $table->uuid('pop_id')->nullable(); // POP owner
            
            // Mikrotik IP Pool Data
            $table->string('mikrotik_id')->nullable(); // .id from Mikrotik
            $table->string('name'); // Pool name
            $table->string('ranges'); // IP ranges e.g. "192.168.1.10-192.168.1.100"
            $table->string('next_pool')->nullable(); // Next pool if this one is exhausted
            
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('ip_pools');
    }
};
