<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_dapat_diakses_tamu(): void
    {
        $this->get('/')->assertOk()->assertSee('NutriMate');
    }

    public function test_tamu_diarahkan_ke_modal_login_bukan_halaman_terpisah(): void
    {
        $this->get('/foods')->assertRedirect(route('home', ['auth' => 'login']));
    }

    public function test_pengguna_dapat_mendaftar_dan_langsung_masuk(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('foods.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
        $this->assertDatabaseHas('profiles', ['full_name' => 'Budi Santoso']);
    }

    public function test_pendaftaran_menolak_email_ganda_dan_kembali_ke_modal(): void
    {
        $this->createUser('Pengguna Lama', 'lama@example.com');

        $response = $this->from(route('home', ['auth' => 'register']))->post('/register', [
            'name' => 'Pengguna Baru',
            'email' => 'lama@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('home', ['auth' => 'register']));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pengguna_dapat_masuk_dengan_kredensial_benar(): void
    {
        $this->createUser('Pengguna Uji', 'uji@example.com', 'rahasia123');

        $response = $this->post('/login', [
            'email' => 'uji@example.com',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('foods.index'));
        $this->assertAuthenticated();
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        $this->createUser('Pengguna Uji', 'uji@example.com', 'rahasia123');

        $response = $this->post('/login', [
            'email' => 'uji@example.com',
            'password' => 'salah-sekali',
        ]);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_pengguna_dapat_keluar(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
