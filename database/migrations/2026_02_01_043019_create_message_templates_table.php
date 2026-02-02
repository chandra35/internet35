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
        Schema::create('message_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pop_id')->nullable()->constrained('users')->nullOnDelete(); // null = global template
            
            // Template identification
            $table->string('code', 50); // customer_welcome, forgot_password, invoice_reminder, etc.
            $table->string('name'); // Display name
            $table->string('description')->nullable();
            
            // Channel type
            $table->enum('channel', ['email', 'whatsapp', 'sms'])->default('email');
            
            // Email specific fields
            $table->string('email_subject')->nullable();
            $table->longText('email_body')->nullable(); // HTML content
            
            // WhatsApp/SMS specific fields
            $table->longText('wa_body')->nullable(); // Plain text with variables
            
            // Template settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // System default template
            
            // Available variables for this template (JSON)
            $table->json('available_variables')->nullable();
            
            $table->timestamps();
            
            // Each POP can have one template per code per channel
            $table->unique(['pop_id', 'code', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
