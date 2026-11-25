<?php

namespace Tests;

use App\Auth\PdoUser;
use App\Database\Connection;
use App\Repositories\FoodRepository;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Repository memakai instance PDO yang di-cache; lepas cache agar
        // setiap pengujian memakai koneksi database yang baru.
        Connection::forget();
    }

    /**
     * Buat pengguna uji beserta profilnya, lalu kembalikan objek auth-nya.
     */
    protected function createUser(
        string $name = 'Pengguna Uji',
        string $email = 'uji@example.com',
        string $password = 'rahasia123',
    ): PdoUser {
        $users = $this->app->make(UserRepository::class);
        $id = $users->create($name, $email, Hash::make($password));

        return PdoUser::fromRow($users->findById($id));
    }

    /**
     * Tambahkan satu makanan uji ke katalog.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function createFood(array $overrides = []): array
    {
        $foods = $this->app->make(FoodRepository::class);

        $data = array_merge([
            'name' => 'Nasi Uji',
            'category' => 'Karbohidrat',
            'price' => 8000,
            'portion_description' => '1 porsi',
            'description' => 'Makanan untuk pengujian.',
            'calories' => 300,
            'protein' => 8,
            'carbs' => 55,
            'fat' => 5,
            'image_url' => null,
        ], $overrides);

        $id = $foods->create($data);

        return $foods->find($id);
    }
}
