<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `users`.
 *
 * Migrasi ini aman dijalankan pada database yang sudah berisi data: bila
 * tabel sudah ada (mis. dari database lama NutriMate), tabel tidak diubah.
 * Aplikasi sendiri mengakses tabel lewat query PDO, bukan Eloquent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
