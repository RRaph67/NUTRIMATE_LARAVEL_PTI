<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pemeriksaan cepat: seluruh halaman utama harus bisa dirender tanpa error
 * setelah pengguna masuk, dan halaman tamu tetap bisa dibuka.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seluruh_halaman_utama_dapat_dirender(): void
    {
        $this->createFood(['name' => 'Nasi Uduk Warteg']);
        $user = $this->createUser();

        $halaman = [
            '/' => 'NutriMate',
            '/foods' => 'Database Makanan',
            '/foods/create' => 'Tambah makanan',
            '/meal-planner' => 'Meal Planner',
            '/recommendation' => 'Rekomendasi Cerdas',
            '/calculator' => 'Hitung Kebutuhan Gizimu',
            '/profile' => 'Data Diri',
        ];

        foreach ($halaman as $url => $teks) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee($teks);
        }
    }

    public function test_halaman_tidak_ditemukan_memakai_tampilan_nutrimate(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');
    }

    public function test_aset_css_dan_js_tersedia(): void
    {
        $this->assertFileExists(public_path('assets/css/app.css'));
        $this->assertFileExists(public_path('assets/js/app.js'));
        $this->assertFileExists(public_path('assets/js/recommendation.js'));
        $this->assertFileExists(public_path('assets/img/logo.svg'));
    }
}
