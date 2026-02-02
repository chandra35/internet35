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
        Schema::create('olt_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olt_id')->constrained('olts')->cascadeOnDelete();
            
            $table->enum('type', ['line', 'service', 'traffic'])->default('line');
            $table->string('name'); // Profile name
            $table->string('profile_id')->nullable(); // ID on OLT
            
            $table->json('config')->nullable(); // Profile configuration
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            $table->unique(['olt_id', 'type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olt_profiles');
    }
};
