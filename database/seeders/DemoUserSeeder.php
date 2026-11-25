<?php

namespace Database\Seeders;

use App\Repositories\UserRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun demo untuk keperluan pengembangan lokal.
 *
 * Hanya dijalankan bila diminta secara eksplisit:
 *   php artisan db:seed --class=DemoUserSeeder
 */
class DemoUserSeeder extends Seeder
{
    public function run(UserRepository $users): void
    {
        $email = 'test@example.com';

        if ($users->findByEmail($email) !== null) {
            $this->command?->warn('Akun demo sudah ada, dilewati.');

            return;
        }

        $users->create('Pengguna Demo', $email, Hash::make('password'));

        $this->command?->info('Akun demo dibuat: '.$email.' / password');
    }
}
