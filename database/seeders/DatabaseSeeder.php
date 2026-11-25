<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder utama aplikasi.
     *
     * Akun demo tidak dibuat otomatis karena berisi kredensial tetap.
     * Jalankan `php artisan db:seed --class=DemoUserSeeder` bila diperlukan.
     */
    public function run(): void
    {
        $this->call([
            FoodSeeder::class,
        ]);
    }
}
