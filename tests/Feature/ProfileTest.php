<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_profil_menampilkan_data_pengguna(): void
    {
        $user = $this->createUser('Budi Santoso', 'budi@example.com');

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('budi@example.com')
            ->assertSee('Data Diri')
            ->assertSee('Keamanan');
    }

    public function test_profil_dapat_diperbarui_beserta_target_dan_budget(): void
    {
        $user = $this->createUser('Nama Lama', 'lama@example.com');

        $this->actingAs($user)->put('/profile', [
            'full_name' => 'Nama Baru',
            'calorie_target' => 2100,
            'daily_budget' => 25000,
        ])->assertRedirect(route('profile.show'));

        // Nama disimpan konsisten di users & profiles.
        $this->assertDatabaseHas('users', ['id' => $user->getAuthIdentifier(), 'name' => 'Nama Baru']);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->getAuthIdentifier(),
            'full_name' => 'Nama Baru',
            'calorie_target' => 2100,
            'daily_budget' => 25000,
        ]);
    }

    public function test_target_kalori_di_luar_rentang_ditolak(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->put('/profile', [
            'full_name' => 'Pengguna Uji',
            'calorie_target' => 100,
            'daily_budget' => 25000,
        ])->assertSessionHasErrors('calorie_target');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->getAuthIdentifier(),
            'calorie_target' => 2000,
        ]);
    }

    public function test_kata_sandi_dapat_diganti(): void
    {
        $user = $this->createUser('Pengguna Uji', 'uji@example.com', 'rahasia123');

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'rahasia123',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect(route('profile.show'));

        // Password baru tersimpan sebagai hash dan bisa dipakai login.
        $this->post('/logout');
        $this->post('/login', ['email' => 'uji@example.com', 'password' => 'sandi-baru-123'])
            ->assertRedirect(route('foods.index'));
        $this->assertAuthenticated();
    }

    public function test_kata_sandi_lama_salah_ditolak(): void
    {
        $user = $this->createUser('Pengguna Uji', 'uji@example.com', 'rahasia123');

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'salah',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect(route('profile.show'))
            ->assertSessionHas('error');
    }

    public function test_konfirmasi_kata_sandi_baru_harus_cocok(): void
    {
        $user = $this->createUser('Pengguna Uji', 'uji@example.com', 'rahasia123');

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'rahasia123',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'beda-sendiri',
        ])->assertSessionHasErrors('password');
    }
}
