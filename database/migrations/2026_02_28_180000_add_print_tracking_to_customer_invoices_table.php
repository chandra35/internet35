<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->timestamp('printed_at')->nullable()->after('payment_reference');
            $table->integer('print_count')->default(0)->after('printed_at');
            $table->uuid('printed_by')->nullable()->after('print_count');
            $table->string('pdf_path')->nullable()->after('printed_by');
            
            $table->foreign('printed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->dropForeign(['printed_by']);
            $table->dropColumn(['printed_at', 'print_count', 'printed_by', 'pdf_path']);
        });
    }
};
