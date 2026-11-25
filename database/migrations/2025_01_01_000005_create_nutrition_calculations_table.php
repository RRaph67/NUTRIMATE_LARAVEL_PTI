<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `nutrition_calculations`: riwayat perhitungan BMR/TDEE/IMT/makro.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nutrition_calculations')) {
            return;
        }

        Schema::create('nutrition_calculations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->unsignedTinyInteger('age');
            $table->decimal('weight', 5, 2);
            $table->decimal('height', 5, 2);
            $table->decimal('activity_factor', 4, 3)->default(1.2);
            $table->string('activity_label', 60)->default('Ringan');
            $table->decimal('bmr', 8, 2)->default(0);
            $table->decimal('tdee', 8, 2)->default(0);
            $table->decimal('protein', 8, 2)->default(0);
            $table->decimal('carbs', 8, 2)->default(0);
            $table->decimal('fat', 8, 2)->default(0);
            $table->decimal('bmi', 5, 2)->default(0);
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_calculations');
    }
};
