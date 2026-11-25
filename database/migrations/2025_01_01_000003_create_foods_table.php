<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `foods`: katalog makanan.
 *
 * Primary key berupa UUID string (bukan auto-increment) karena ID makanan
 * ikut tersimpan pada riwayat Meal Planner dan payload rekomendasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('foods')) {
            return;
        }

        Schema::create('foods', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            $table->string('category', 100)->index();
            $table->decimal('price', 12, 2);
            $table->string('portion_description');
            $table->text('description')->nullable();
            $table->decimal('calories', 8, 2);
            $table->decimal('protein', 6, 2);
            $table->decimal('carbs', 6, 2);
            $table->decimal('fat', 6, 2);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
