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
        Schema::table('pop_settings', function (Blueprint $table) {
            // SMTP Settings
            $table->boolean('smtp_enabled')->default(false)->after('facebook');
            $table->string('smtp_host')->nullable()->after('smtp_enabled');
            $table->integer('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_username'); // encrypted
            $table->enum('smtp_encryption', ['tls', 'ssl', 'none'])->default('tls')->after('smtp_password');
            $table->string('smtp_from_address')->nullable()->after('smtp_encryption');
            $table->string('smtp_from_name')->nullable()->after('smtp_from_address');
            
            // WhatsApp Gateway Settings
            $table->boolean('wa_enabled')->default(false)->after('smtp_from_name');
            $table->enum('wa_provider', ['fonnte', 'wablas', 'dripsender', 'custom'])->default('fonnte')->after('wa_enabled');
            $table->string('wa_api_url')->nullable()->after('wa_provider');
            $table->text('wa_api_key')->nullable()->after('wa_api_url'); // encrypted
            $table->string('wa_sender_number')->nullable()->after('wa_api_key');
            
            // SMS Gateway Settings (optional for future)
            $table->boolean('sms_enabled')->default(false)->after('wa_sender_number');
            $table->enum('sms_provider', ['twilio', 'nexmo', 'custom'])->nullable()->after('sms_enabled');
            $table->string('sms_api_url')->nullable()->after('sms_provider');
            $table->text('sms_api_key')->nullable()->after('sms_api_url'); // encrypted
            $table->string('sms_sender_id')->nullable()->after('sms_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pop_settings', function (Blueprint $table) {
            // SMTP
            $table->dropColumn([
                'smtp_enabled',
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_password',
                'smtp_encryption',
                'smtp_from_address',
                'smtp_from_name',
            ]);
            
            // WhatsApp
            $table->dropColumn([
                'wa_enabled',
                'wa_provider',
                'wa_api_url',
                'wa_api_key',
                'wa_sender_number',
            ]);
            
            // SMS
            $table->dropColumn([
                'sms_enabled',
                'sms_provider',
                'sms_api_url',
                'sms_api_key',
                'sms_sender_id',
            ]);
        });
    }
};
