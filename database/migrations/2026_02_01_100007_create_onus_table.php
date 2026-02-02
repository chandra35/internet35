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
        Schema::create('onus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->foreignUuid('pon_port_id')->nullable()->constrained('olt_pon_ports')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('odp_id')->nullable()->constrained('odps')->nullOnDelete();
            
            // ONU Identification
            $table->string('serial_number'); // HWTC12345678
            $table->string('mac_address')->nullable();
            $table->string('name')->nullable(); // Custom name
            
            // OLT Position
            $table->integer('slot')->default(0);
            $table->integer('port');
            $table->integer('onu_id'); // ONU ID on PON port (1-128)
            
            // ONU Device Info
            $table->string('onu_type')->nullable(); // Model type
            $table->string('vendor')->nullable(); // Manufacturer
            $table->string('software_version')->nullable();
            $table->string('hardware_version')->nullable();
            
            // Status
            $table->enum('status', ['online', 'offline', 'los', 'dying_gasp', 'power_off', 'unknown'])->default('unknown');
            $table->enum('config_status', ['registered', 'unregistered', 'configuring', 'failed'])->default('unregistered');
            $table->enum('auth_status', ['authenticated', 'unauthenticated', 'blocked'])->default('unauthenticated');
            
            // Optical Metrics
            $table->decimal('rx_power', 8, 2)->nullable(); // ONU receive power (dBm)
            $table->decimal('tx_power', 8, 2)->nullable(); // ONU transmit power (dBm)
            $table->decimal('olt_rx_power', 8, 2)->nullable(); // OLT receive power from this ONU (dBm)
            $table->decimal('temperature', 8, 2)->nullable(); // Temperature in Celsius
            $table->decimal('voltage', 8, 2)->nullable(); // Supply voltage
            $table->decimal('bias_current', 8, 2)->nullable(); // Laser bias current (mA)
            
            // Distance & Performance
            $table->decimal('distance', 10, 2)->nullable(); // Distance from OLT (meters)
            $table->integer('uptime_seconds')->nullable(); // Uptime in seconds
            $table->timestamp('last_online_at')->nullable();
            $table->timestamp('last_offline_at')->nullable();
            
            // Traffic Statistics
            $table->bigInteger('in_octets')->default(0);
            $table->bigInteger('out_octets')->default(0);
            $table->bigInteger('in_packets')->default(0);
            $table->bigInteger('out_packets')->default(0);
            
            // Service Profile (for ZTE provisioning)
            $table->string('line_profile')->nullable();
            $table->string('service_profile')->nullable();
            $table->json('service_ports')->nullable(); // Array of service port configs
            $table->json('vlan_config')->nullable(); // VLAN configuration
            
            // IP Configuration
            $table->string('mgmt_ip')->nullable(); // Management IP if assigned
            $table->string('wan_ip')->nullable(); // WAN IP
            $table->string('pppoe_username')->nullable();
            
            // Additional Info
            $table->integer('odp_port')->nullable(); // Port on ODP
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamp('last_sync_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['olt_id', 'serial_number']);
            $table->unique(['olt_id', 'slot', 'port', 'onu_id']);
            $table->index(['status', 'config_status']);
            $table->index(['customer_id']);
            $table->index(['odp_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onus');
    }
};
