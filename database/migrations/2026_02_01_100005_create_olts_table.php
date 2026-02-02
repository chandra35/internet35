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
        Schema::create('olts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pop_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('router_id')->nullable()->constrained('routers')->nullOnDelete();
            
            $table->string('name'); // OLT-01, OLT-Desa-A
            $table->string('code')->unique(); // Unique code for identification
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            
            // Brand & Model
            $table->enum('brand', ['zte', 'hioso', 'hsgq', 'vsol', 'huawei', 'fiberhome', 'bdcom', 'other'])->default('zte');
            $table->string('model')->nullable(); // C320, C300, MA5608T, etc
            $table->string('firmware_version')->nullable();
            $table->string('serial_number')->nullable();
            
            // Connection Settings
            $table->string('ip_address');
            $table->integer('snmp_port')->default(161);
            $table->string('snmp_community')->default('public');
            $table->string('snmp_version')->default('2c'); // 1, 2c, 3
            
            // SNMPv3 Settings (if needed)
            $table->string('snmp_username')->nullable();
            $table->string('snmp_auth_protocol')->nullable(); // MD5, SHA
            $table->text('snmp_auth_password')->nullable();
            $table->string('snmp_priv_protocol')->nullable(); // DES, AES
            $table->text('snmp_priv_password')->nullable();
            
            // Telnet/SSH Settings
            $table->boolean('telnet_enabled')->default(false);
            $table->integer('telnet_port')->default(23);
            $table->string('telnet_username')->nullable();
            $table->text('telnet_password')->nullable();
            
            $table->boolean('ssh_enabled')->default(false);
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_username')->nullable();
            $table->text('ssh_password')->nullable();
            $table->text('ssh_key')->nullable();
            
            // API Settings (for some OLT with REST API)
            $table->boolean('api_enabled')->default(false);
            $table->string('api_url')->nullable();
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            
            // Location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Capacity
            $table->integer('total_pon_ports')->default(8); // PON ports count
            $table->integer('total_uplink_ports')->default(4); // Uplink ports
            $table->integer('max_onu_per_port')->default(128); // Max ONU per PON port
            
            // Status
            $table->enum('status', ['active', 'inactive', 'maintenance', 'offline'])->default('active');
            $table->timestamp('last_sync_at')->nullable(); // Last successful data sync
            $table->timestamp('last_online_at')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['pop_id', 'status']);
            $table->index(['brand', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olts');
    }
};
