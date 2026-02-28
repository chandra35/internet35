<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('splitter_ratios', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['equal', 'unequal'])->default('equal');
            $table->string('ratio', 20); // e.g. "1:8", "25:75"
            $table->string('name', 100); // Display name
            $table->decimal('branch_loss', 5, 2); // Loss untuk branch (ke pelanggan) dalam dB
            $table->decimal('relay_loss', 5, 2)->nullable(); // Loss untuk relay (ke ODP berikut) dalam dB
            $table->integer('branch_percent')->nullable(); // Persentase ke branch (untuk unequal)
            $table->integer('relay_percent')->nullable(); // Persentase ke relay (untuk unequal)
            $table->integer('ports')->nullable(); // Jumlah port (untuk equal)
            $table->string('branch_color', 20)->default('#007bff'); // Warna branch (biru)
            $table->string('relay_color', 20)->default('#dc3545'); // Warna relay (merah)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['type', 'ratio']);
        });
        
        // Seed default splitter data
        $this->seedDefaultSplitters();
    }
    
    /**
     * Seed default splitter ratios
     */
    private function seedDefaultSplitters(): void
    {
        $now = now();
        
        // Equal Splitters (1:N) - semua port sama
        $equalSplitters = [
            ['ratio' => '1:2', 'name' => 'Splitter 1:2', 'branch_loss' => 3.5, 'ports' => 2, 'sort_order' => 1],
            ['ratio' => '1:4', 'name' => 'Splitter 1:4', 'branch_loss' => 7.0, 'ports' => 4, 'sort_order' => 2],
            ['ratio' => '1:8', 'name' => 'Splitter 1:8', 'branch_loss' => 10.5, 'ports' => 8, 'sort_order' => 3],
            ['ratio' => '1:16', 'name' => 'Splitter 1:16', 'branch_loss' => 14.0, 'ports' => 16, 'sort_order' => 4],
            ['ratio' => '1:32', 'name' => 'Splitter 1:32', 'branch_loss' => 17.5, 'ports' => 32, 'sort_order' => 5],
            ['ratio' => '1:64', 'name' => 'Splitter 1:64', 'branch_loss' => 21.0, 'ports' => 64, 'sort_order' => 6],
        ];
        
        foreach ($equalSplitters as $splitter) {
            DB::table('splitter_ratios')->insert([
                'type' => 'equal',
                'ratio' => $splitter['ratio'],
                'name' => $splitter['name'],
                'branch_loss' => $splitter['branch_loss'],
                'relay_loss' => null,
                'branch_percent' => null,
                'relay_percent' => null,
                'ports' => $splitter['ports'],
                'branch_color' => '#007bff',
                'relay_color' => '#dc3545',
                'description' => 'Splitter equal dengan ' . $splitter['ports'] . ' port output',
                'is_active' => true,
                'sort_order' => $splitter['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        // Unequal/Ratio Splitters - Branch (ke pelanggan) : Relay (ke ODP berikut)
        // Angka pertama = % ke branch (pelanggan), Angka kedua = % sisa ke relay
        // Formula Loss: -10 × log10(percent / 100)
        $unequalSplitters = [
            // Rasio kecil ke branch (lebih banyak power ke relay untuk cascade panjang)
            ['ratio' => '2:98', 'name' => 'Rasio 2:98', 'branch_percent' => 2, 'relay_percent' => 98, 'branch_loss' => 17.0, 'relay_loss' => 0.09, 'sort_order' => 1],
            ['ratio' => '3:97', 'name' => 'Rasio 3:97', 'branch_percent' => 3, 'relay_percent' => 97, 'branch_loss' => 15.2, 'relay_loss' => 0.13, 'sort_order' => 2],
            ['ratio' => '4:96', 'name' => 'Rasio 4:96', 'branch_percent' => 4, 'relay_percent' => 96, 'branch_loss' => 14.0, 'relay_loss' => 0.18, 'sort_order' => 3],
            ['ratio' => '5:95', 'name' => 'Rasio 5:95', 'branch_percent' => 5, 'relay_percent' => 95, 'branch_loss' => 13.0, 'relay_loss' => 0.22, 'sort_order' => 4],
            ['ratio' => '6:94', 'name' => 'Rasio 6:94', 'branch_percent' => 6, 'relay_percent' => 94, 'branch_loss' => 12.2, 'relay_loss' => 0.27, 'sort_order' => 5],
            ['ratio' => '7:93', 'name' => 'Rasio 7:93', 'branch_percent' => 7, 'relay_percent' => 93, 'branch_loss' => 11.5, 'relay_loss' => 0.32, 'sort_order' => 6],
            ['ratio' => '8:92', 'name' => 'Rasio 8:92', 'branch_percent' => 8, 'relay_percent' => 92, 'branch_loss' => 11.0, 'relay_loss' => 0.36, 'sort_order' => 7],
            ['ratio' => '9:91', 'name' => 'Rasio 9:91', 'branch_percent' => 9, 'relay_percent' => 91, 'branch_loss' => 10.5, 'relay_loss' => 0.41, 'sort_order' => 8],
            ['ratio' => '10:90', 'name' => 'Rasio 10:90', 'branch_percent' => 10, 'relay_percent' => 90, 'branch_loss' => 10.0, 'relay_loss' => 0.46, 'sort_order' => 9],
            
            // Rasio medium
            ['ratio' => '12:88', 'name' => 'Rasio 12:88', 'branch_percent' => 12, 'relay_percent' => 88, 'branch_loss' => 9.2, 'relay_loss' => 0.56, 'sort_order' => 10],
            ['ratio' => '15:85', 'name' => 'Rasio 15:85', 'branch_percent' => 15, 'relay_percent' => 85, 'branch_loss' => 8.2, 'relay_loss' => 0.71, 'sort_order' => 11],
            ['ratio' => '18:82', 'name' => 'Rasio 18:82', 'branch_percent' => 18, 'relay_percent' => 82, 'branch_loss' => 7.4, 'relay_loss' => 0.86, 'sort_order' => 12],
            ['ratio' => '20:80', 'name' => 'Rasio 20:80', 'branch_percent' => 20, 'relay_percent' => 80, 'branch_loss' => 7.0, 'relay_loss' => 0.97, 'sort_order' => 13],
            ['ratio' => '22:78', 'name' => 'Rasio 22:78', 'branch_percent' => 22, 'relay_percent' => 78, 'branch_loss' => 6.6, 'relay_loss' => 1.08, 'sort_order' => 14],
            ['ratio' => '25:75', 'name' => 'Rasio 25:75', 'branch_percent' => 25, 'relay_percent' => 75, 'branch_loss' => 6.0, 'relay_loss' => 1.25, 'sort_order' => 15],
            ['ratio' => '28:72', 'name' => 'Rasio 28:72', 'branch_percent' => 28, 'relay_percent' => 72, 'branch_loss' => 5.5, 'relay_loss' => 1.43, 'sort_order' => 16],
            ['ratio' => '30:70', 'name' => 'Rasio 30:70', 'branch_percent' => 30, 'relay_percent' => 70, 'branch_loss' => 5.2, 'relay_loss' => 1.55, 'sort_order' => 17],
            
            // Rasio seimbang
            ['ratio' => '33:67', 'name' => 'Rasio 33:67', 'branch_percent' => 33, 'relay_percent' => 67, 'branch_loss' => 4.8, 'relay_loss' => 1.74, 'sort_order' => 18],
            ['ratio' => '35:65', 'name' => 'Rasio 35:65', 'branch_percent' => 35, 'relay_percent' => 65, 'branch_loss' => 4.6, 'relay_loss' => 1.87, 'sort_order' => 19],
            ['ratio' => '38:62', 'name' => 'Rasio 38:62', 'branch_percent' => 38, 'relay_percent' => 62, 'branch_loss' => 4.2, 'relay_loss' => 2.08, 'sort_order' => 20],
            ['ratio' => '40:60', 'name' => 'Rasio 40:60', 'branch_percent' => 40, 'relay_percent' => 60, 'branch_loss' => 4.0, 'relay_loss' => 2.22, 'sort_order' => 21],
            ['ratio' => '42:58', 'name' => 'Rasio 42:58', 'branch_percent' => 42, 'relay_percent' => 58, 'branch_loss' => 3.8, 'relay_loss' => 2.37, 'sort_order' => 22],
            ['ratio' => '45:55', 'name' => 'Rasio 45:55', 'branch_percent' => 45, 'relay_percent' => 55, 'branch_loss' => 3.5, 'relay_loss' => 2.60, 'sort_order' => 23],
            ['ratio' => '48:52', 'name' => 'Rasio 48:52', 'branch_percent' => 48, 'relay_percent' => 52, 'branch_loss' => 3.2, 'relay_loss' => 2.84, 'sort_order' => 24],
            ['ratio' => '50:50', 'name' => 'Rasio 50:50', 'branch_percent' => 50, 'relay_percent' => 50, 'branch_loss' => 3.0, 'relay_loss' => 3.0, 'sort_order' => 25],
        ];
        
        foreach ($unequalSplitters as $splitter) {
            DB::table('splitter_ratios')->insert([
                'type' => 'unequal',
                'ratio' => $splitter['ratio'],
                'name' => $splitter['name'],
                'branch_loss' => $splitter['branch_loss'],
                'relay_loss' => $splitter['relay_loss'],
                'branch_percent' => $splitter['branch_percent'],
                'relay_percent' => $splitter['relay_percent'],
                'ports' => null,
                'branch_color' => '#007bff', // Biru untuk branch
                'relay_color' => '#dc3545', // Merah untuk relay
                'description' => $splitter['branch_percent'] . '% ke splitter pelanggan (biru), ' . $splitter['relay_percent'] . '% sisa untuk relay (merah)',
                'is_active' => true,
                'sort_order' => $splitter['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('splitter_ratios');
    }
};
