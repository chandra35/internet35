<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmware_files', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 20);           // huawei, zte, fiberhome, ...
            $table->string('model_pattern', 100)->nullable(); // HG8145V5, HG8245*, atau null = semua model brand ini
            $table->string('version', 100);        // V5R021C10S030, dsb
            $table->string('filename', 255);       // nama file di storage
            $table->string('original_name', 255);  // nama file asli saat upload
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['brand', 'model_pattern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmware_files');
    }
};
