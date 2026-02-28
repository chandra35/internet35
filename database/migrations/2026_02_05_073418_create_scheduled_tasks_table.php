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
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('command');
            $table->string('schedule'); // cron expression or preset like 'daily', 'hourly'
            $table->string('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->enum('last_status', ['pending', 'running', 'success', 'failed'])->default('pending');
            $table->text('last_output')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->integer('timeout')->default(3600); // in seconds
            $table->boolean('without_overlapping')->default(true);
            $table->boolean('run_in_background')->default(false);
            $table->foreignUuid('pop_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['is_enabled', 'next_run_at']);
            $table->index('pop_id');
        });
        
        // Task run history
        Schema::create('scheduled_task_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('scheduled_task_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->text('output')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->string('triggered_by')->default('scheduler'); // scheduler, manual, api
            $table->foreignUuid('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['scheduled_task_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_logs');
        Schema::dropIfExists('scheduled_tasks');
    }
};
