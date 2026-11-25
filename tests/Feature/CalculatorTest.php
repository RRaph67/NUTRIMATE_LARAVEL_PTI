<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_kalkulator_tampil_untuk_pengguna_masuk(): void
    {
        $this->actingAs($this->createUser())
            ->get('/calculator')
            ->assertOk()
            ->assertSee('Hitung Kebutuhan Gizimu')
            ->assertSee('Belum Ada Hasil');
    }

    public function test_perhitungan_disimpan_sebagai_riwayat_dan_menyetel_target_profil(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post('/calculator', [
            'gender' => 'female',
            'age' => 21,
            'weight' => 55,
            'height' => 160,
            'activity' => '1.375',
        ])->assertRedirect(route('calculator.index'));

        $this->assertDatabaseHas('nutrition_calculations', [
            'user_id' => $user->getAuthIdentifier(),
            'gender' => 'female',
            'age' => 21,
            'bmr' => 1284,
            'tdee' => 1766,
        ]);

        // TDEE langsung menjadi target kalori profil (budget tidak berubah).
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->getAuthIdentifier(),
            'calorie_target' => 1766,
        ]);

        $this->actingAs($user)
            ->get('/calculator')
            ->assertOk()
            ->assertSee('1.766')
            ->assertSee('Riwayat Perhitungan');
    }

    public function test_validasi_menolak_data_tubuh_di_luar_rentang(): void
    {
        $this->actingAs($this->createUser())->post('/calculator', [
            'gender' => 'male',
            'age' => 5,
            'weight' => 10,
            'height' => 300,
            'activity' => '9.9',
        ])->assertSessionHasErrors(['age', 'weight', 'height', 'activity']);

        $this->assertDatabaseCount('nutrition_calculations', 0);
    }

    public function test_riwayat_perhitungan_dapat_dihapus_satu_per_satu(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post('/calculator', [
            'gender' => 'male',
            'age' => 20,
            'weight' => 60,
            'height' => 170,
            'activity' => '1.2',
        ]);

        $id = $this->actingAs($user)->get('/calculator')->viewData('history')[0]['id'];

        $this->actingAs($user)
            ->delete("/calculator/history/{$id}")
            ->assertRedirect(route('calculator.index'));

        $this->assertDatabaseCount('nutrition_calculations', 0);
    }

    public function test_riwayat_milik_pengguna_lain_tidak_dapat_dihapus(): void
    {
        $lain = $this->createUser('Orang Lain', 'lain@example.com');

        $this->actingAs($lain)->post('/calculator', [
            'gender' => 'male',
            'age' => 20,
            'weight' => 60,
            'height' => 170,
            'activity' => '1.2',
        ]);

        $id = $this->actingAs($lain)->get('/calculator')->viewData('history')[0]['id'];

        $this->actingAs($this->createUser('Saya', 'saya@example.com'))
            ->delete("/calculator/history/{$id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('nutrition_calculations', ['id' => $id]);
    }

    public function test_seluruh_riwayat_dapat_dibersihkan(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post('/calculator', [
            'gender' => 'male',
            'age' => 20,
            'weight' => 60,
            'height' => 170,
            'activity' => '1.2',
        ]);

        $this->actingAs($user)
            ->delete('/calculator/history')
            ->assertRedirect(route('calculator.index'));

        $this->assertDatabaseCount('nutrition_calculations', 0);
    }

    public function test_menu_builder_menghitung_total_dan_menggabungkan_porsi(): void
    {
        $food = $this->createFood([
            'name' => 'Nasi Uduk',
            'calories' => 300,
            'protein' => 8,
            'carbs' => 55,
            'fat' => 5,
            'price' => 8000,
        ]);
        $user = $this->createUser();

        $this->actingAs($user)->post('/calculator/items', [
            'food_id' => $food['id'],
            'portions' => 1,
        ])->assertRedirect(route('calculator.index'));

        // Menambah makanan yang sama tidak menduplikasi baris, hanya porsinya.
        $this->actingAs($user)->post('/calculator/items', [
            'food_id' => $food['id'],
            'portions' => 2,
        ]);

        $halaman = $this->actingAs($user)->get('/calculator');
        $items = $halaman->viewData('items');
        $totals = $halaman->viewData('totals');

        $this->assertCount(1, $items);
        $this->assertSame(2, $items[0]['portions']);
        $this->assertSame(600.0, $totals['calories']);
        $this->assertSame(16000.0, $totals['price']);
    }

    public function test_menu_builder_menolak_porsi_di_luar_batas(): void
    {
        $food = $this->createFood();

        $this->actingAs($this->createUser())->post('/calculator/items', [
            'food_id' => $food['id'],
            'portions' => 3,
        ])->assertSessionHasErrors('portions');
    }

    public function test_menu_builder_dapat_dikosongkan(): void
    {
        $food = $this->createFood();
        $user = $this->createUser();

        $this->actingAs($user)->post('/calculator/items', ['food_id' => $food['id'], 'portions' => 1]);
        $this->actingAs($user)->delete('/calculator/items/all')->assertRedirect(route('calculator.index'));

        $this->assertSame([], $this->actingAs($user)->get('/calculator')->viewData('items'));
    }

    public function test_susunan_menu_dapat_disimpan_ke_meal_planner(): void
    {
        $food = $this->createFood(['name' => 'Nasi Uduk', 'calories' => 300, 'price' => 8000]);
        $user = $this->createUser();

        $this->actingAs($user)->post('/calculator/items', ['food_id' => $food['id'], 'portions' => 2]);

        $this->actingAs($user)
            ->post('/calculator/save-to-meal-plan', ['slot' => 'makan_malam'])
            ->assertRedirect(route('meal-planner.index'));

        $this->assertDatabaseHas('meal_plans', [
            'user_id' => $user->getAuthIdentifier(),
            'meal_slot' => 'makan_malam',
            'label' => 'Nasi Uduk · 2 porsi',
            'calories' => 600,
        ]);

        // Session menu dibersihkan setelah disimpan.
        $this->assertSame([], $this->actingAs($user)->get('/calculator')->viewData('items'));
    }

    public function test_susunan_menu_kosong_tidak_menyimpan_apa_pun(): void
    {
        $this->actingAs($this->createUser())
            ->post('/calculator/save-to-meal-plan', ['slot' => 'sarapan'])
            ->assertRedirect(route('calculator.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('meal_plans', 0);
    }
}
