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
        // Provinces table
        if (!Schema::hasTable('indonesia_provinces')) {
            Schema::create('indonesia_provinces', function (Blueprint $table) {
                $table->char('code', 2)->primary();
                $table->string('name', 255);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        // Cities/Regencies table
        if (!Schema::hasTable('indonesia_cities')) {
            Schema::create('indonesia_cities', function (Blueprint $table) {
                $table->char('code', 4)->primary();
                $table->char('province_code', 2);
                $table->string('name', 255);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('province_code')
                    ->references('code')
                    ->on('indonesia_provinces')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        // Districts table
        if (!Schema::hasTable('indonesia_districts')) {
            Schema::create('indonesia_districts', function (Blueprint $table) {
                $table->char('code', 7)->primary();
                $table->char('city_code', 4);
                $table->string('name', 255);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('city_code')
                    ->references('code')
                    ->on('indonesia_cities')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        // Villages table
        if (!Schema::hasTable('indonesia_villages')) {
            Schema::create('indonesia_villages', function (Blueprint $table) {
                $table->char('code', 10)->primary();
                $table->char('district_code', 7);
                $table->string('name', 255);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('district_code')
                    ->references('code')
                    ->on('indonesia_districts')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indonesia_villages');
        Schema::dropIfExists('indonesia_districts');
        Schema::dropIfExists('indonesia_cities');
        Schema::dropIfExists('indonesia_provinces');
    }
};
