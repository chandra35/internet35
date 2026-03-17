<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pop_id')->index();
            $table->uuid('customer_id')->nullable()->index();
            $table->string('channel', 20)->index(); // email, whatsapp, telegram
            $table->string('template_code', 50)->nullable()->index();
            $table->string('recipient', 255); // email address or phone number
            $table->string('subject', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 20)->default('pending'); // pending, sent, failed, bounced
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // extra info (gateway response, message id, etc.)
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('pop_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
