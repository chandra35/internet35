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
        Schema::create('routers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('identity')->nullable(); // Mikrotik identity
            $table->string('host'); // IP or hostname
            $table->integer('api_port')->default(8728);
            $table->integer('api_ssl_port')->default(8729);
            $table->boolean('use_ssl')->default(false);
            $table->string('username');
            $table->text('password'); // encrypted
            $table->string('ros_version')->nullable(); // 6.x or 7.x
            $table->integer('ros_major_version')->nullable(); // 6 or 7
            $table->string('board_name')->nullable();
            $table->string('architecture')->nullable();
            $table->string('cpu')->nullable();
            $table->bigInteger('total_memory')->nullable();
            $table->bigInteger('free_memory')->nullable();
            $table->bigInteger('total_hdd_space')->nullable();
            $table->bigInteger('free_hdd_space')->nullable();
            $table->string('uptime')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('pop_id')->nullable(); // Owner POP
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('host');
            $table->index('status');
            $table->index('pop_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
