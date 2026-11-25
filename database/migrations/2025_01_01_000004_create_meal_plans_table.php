<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `meal_plans`: rencana makan harian per slot.
 *
 * Nilai gizi & harga disimpan langsung (denormalisasi) agar rencana makan
 * yang sudah dibuat tetap stabil walaupun data makanan berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meal_plans')) {
            return;
        }

        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('entry_date');
            $table->string('meal_slot', 20);
            $table->string('label');
            $table->char('food_id', 36)->nullable();
            $table->decimal('calories', 8, 2)->default(0);
            $table->decimal('protein', 8, 2)->default(0);
            $table->decimal('carbs', 8, 2)->default(0);
            $table->decimal('fat', 8, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plans');
    }
};
