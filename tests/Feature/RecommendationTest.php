<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Isi katalog dengan satu makanan untuk setiap kategori agar mesin
     * rekomendasi punya kandidat lengkap di ketiga slot makan.
     */
    private function seedCatalog(): void
    {
        $this->createFood(['name' => 'Nasi Putih', 'category' => 'Karbohidrat', 'price' => 4000, 'calories' => 250, 'protein' => 5, 'carbs' => 55, 'fat' => 1]);
        $this->createFood(['name' => 'Tempe Orek', 'category' => 'Protein', 'price' => 4000, 'calories' => 165, 'protein' => 9, 'carbs' => 12, 'fat' => 9]);
        $this->createFood(['name' => 'Sayur Sop', 'category' => 'Sayuran', 'price' => 3000, 'calories' => 90, 'protein' => 3, 'carbs' => 10, 'fat' => 3]);
        $this->createFood(['name' => 'Pisang', 'category' => 'Buah', 'price' => 3000, 'calories' => 105, 'protein' => 1, 'carbs' => 27, 'fat' => 0]);
    }

    public function test_halaman_rekomendasi_memakai_budget_dan_target_dari_profil(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->put('/profile', [
            'full_name' => 'Pengguna Uji',
            'calorie_target' => 2100,
            'daily_budget' => 27000,
        ]);

        $this->actingAs($user)
            ->get('/recommendation')
            ->assertOk()
            ->assertSee('27000')
            ->assertSee('targetCalories: 2100');
    }

    public function test_endpoint_json_mengembalikan_paket_lengkap(): void
    {
        $this->seedCatalog();

        $response = $this->actingAs($this->createUser())
            ->postJson('/api/recommend', [
                'budget' => 30000,
                'focus' => ['hemat'],
                'target_calories' => 2000,
                'count' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'packages.0.meals');

        $paket = $response->json('packages.0');

        // Paket berisi total, gizi, dan payload siap dikirim ke Meal Planner.
        $this->assertArrayHasKey('total', $paket);
        $this->assertArrayHasKey('nutrition', $paket);
        $this->assertNotEmpty($paket['payload']);
        $this->assertSame(['sarapan', 'makan_siang', 'makan_malam'], array_column($paket['meals'], 'slot'));
    }

    public function test_endpoint_menolak_budget_di_bawah_minimum(): void
    {
        $this->seedCatalog();

        $this->actingAs($this->createUser())
            ->postJson('/api/recommend', ['budget' => 5000])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_endpoint_menolak_budget_di_atas_maksimum(): void
    {
        $this->seedCatalog();

        $this->actingAs($this->createUser())
            ->postJson('/api/recommend', ['budget' => 5000000])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_endpoint_menolak_budget_bukan_angka(): void
    {
        $this->seedCatalog();

        $this->actingAs($this->createUser())
            ->postJson('/api/recommend', ['budget' => 'banyak'])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_endpoint_tamu_tidak_dapat_diakses(): void
    {
        $this->postJson('/api/recommend', ['budget' => 30000])
            ->assertUnauthorized();
    }

    public function test_preferensi_sayur_dan_buah_dihormati(): void
    {
        $this->seedCatalog();

        $response = $this->actingAs($this->createUser())
            ->postJson('/api/recommend', [
                'budget' => 60000,
                'focus' => ['sayur', 'buah'],
                'target_calories' => 2000,
            ])
            ->assertOk();

        $menu = implode(' ', array_column($response->json('packages.0.meals'), 'menu'));

        $this->assertStringContainsString('Sayur Sop', $menu);
        $this->assertStringContainsString('Pisang', $menu);
    }

    public function test_katalog_kosong_memberi_catatan_bukan_error(): void
    {
        $response = $this->actingAs($this->createUser())
            ->postJson('/api/recommend', ['budget' => 30000])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame([], $response->json('packages'));
        $this->assertNotSame('', $response->json('meta.note'));
    }

    public function test_paket_rekomendasi_dapat_diterapkan_ke_meal_planner(): void
    {
        $this->seedCatalog();
        $user = $this->createUser();

        $payload = $this->actingAs($user)
            ->postJson('/api/recommend', ['budget' => 30000, 'focus' => ['hemat']])
            ->json('packages.0.payload');

        $this->actingAs($user)
            ->post('/recommendation/apply', ['payload' => json_encode($payload)])
            ->assertRedirect(route('meal-planner.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('meal_plans', count($payload));
    }

    public function test_paket_yang_sama_tidak_ditambahkan_dua_kali(): void
    {
        $this->seedCatalog();
        $user = $this->createUser();

        $payload = $this->actingAs($user)
            ->postJson('/api/recommend', ['budget' => 30000, 'focus' => ['hemat']])
            ->json('packages.0.payload');

        $this->actingAs($user)->post('/recommendation/apply', ['payload' => json_encode($payload)]);
        $jumlahAwal = count($payload);

        $this->actingAs($user)
            ->post('/recommendation/apply', ['payload' => json_encode($payload)])
            ->assertRedirect(route('recommendation.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('meal_plans', $jumlahAwal);
    }

    public function test_payload_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->createUser())
            ->post('/recommendation/apply', ['payload' => 'bukan-json'])
            ->assertRedirect(route('recommendation.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('meal_plans', 0);
    }
}
