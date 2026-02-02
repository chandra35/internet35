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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Owner (admin-pop)
            
            // Email Notification
            $table->boolean('email_enabled')->default(true);
            $table->string('email_from_name')->nullable();
            $table->string('email_from_address')->nullable();
            $table->string('email_reply_to')->nullable();
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable(); // Encrypted
            $table->string('smtp_encryption')->nullable(); // tls, ssl
            
            // WhatsApp Notification
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_provider')->nullable(); // fonnte, wablas, woowa, etc
            $table->text('whatsapp_api_key')->nullable(); // Encrypted
            $table->string('whatsapp_sender')->nullable();
            $table->string('whatsapp_device_id')->nullable();
            
            // Telegram Notification
            $table->boolean('telegram_enabled')->default(false);
            $table->text('telegram_bot_token')->nullable(); // Encrypted
            $table->string('telegram_chat_id')->nullable();
            
            // Notification Templates
            $table->json('templates')->nullable(); // Custom templates per event
            
            // Notification Events (which events trigger notification)
            $table->json('enabled_events')->nullable();
            /*
             * Events:
             * - invoice_created
             * - invoice_due_reminder (1 day, 3 days, 7 days before)
             * - invoice_overdue
             * - payment_received
             * - payment_failed
             * - subscription_activated
             * - subscription_expired
             * - subscription_expiring_soon
             * - customer_registered
             * - customer_suspended
             * - customer_unsuspended
             */
            
            // Schedule Settings
            $table->string('reminder_time', 5)->default('09:00'); // HH:MM format
            $table->json('reminder_days_before')->nullable(); // [1, 3, 7]
            
            $table->timestamps();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
