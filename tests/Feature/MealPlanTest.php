<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_meal_planner_menampilkan_tiga_slot_kosong(): void
    {
        $this->actingAs($this->createUser())
            ->get('/meal-planner')
            ->assertOk()
            ->assertSee('Sarapan')
            ->assertSee('Makan Siang')
            ->assertSee('Makan Malam')
            ->assertSee('Belum ada makanan');
    }

    public function test_makanan_dapat_ditambahkan_ke_slot_dengan_porsi(): void
    {
        $food = $this->createFood(['name' => 'Nasi Uduk', 'calories' => 300, 'price' => 8000]);
        $user = $this->createUser();

        $this->actingAs($user)->post('/meal-planner/food', [
            'food_id' => $food['id'],
            'slot' => 'sarapan',
            'portions' => 2,
        ])->assertRedirect(route('meal-planner.index'));

        // Gizi & harga dikalikan jumlah porsi.
        $this->assertDatabaseHas('meal_plans', [
            'user_id' => $user->getAuthIdentifier(),
            'meal_slot' => 'sarapan',
            'label' => 'Nasi Uduk · 2 porsi',
            'calories' => 600,
            'price' => 16000,
        ]);

        $this->actingAs($user)
            ->get('/meal-planner')
            ->assertSee('Nasi Uduk · 2 porsi')
            ->assertSee('600');
    }

    public function test_slot_tidak_sah_ditolak(): void
    {
        $food = $this->createFood();

        $this->actingAs($this->createUser())->post('/meal-planner/food', [
            'food_id' => $food['id'],
            'slot' => 'ngemil',
        ])->assertSessionHasErrors('slot');

        $this->assertDatabaseCount('meal_plans', 0);
    }

    public function test_porsi_di_atas_batas_ditolak(): void
    {
        $food = $this->createFood();

        $this->actingAs($this->createUser())->post('/meal-planner/food', [
            'food_id' => $food['id'],
            'slot' => 'sarapan',
            'portions' => 5,
        ])->assertSessionHasErrors('portions');
    }

    public function test_makanan_tidak_ditemukan_tidak_menambah_baris(): void
    {
        $this->actingAs($this->createUser())->post('/meal-planner/food', [
            'food_id' => 'id-palsu',
            'slot' => 'sarapan',
        ])->assertRedirect(route('foods.index'));

        $this->assertDatabaseCount('meal_plans', 0);
    }

    public function test_slot_dapat_dikosongkan(): void
    {
        $food = $this->createFood();
        $user = $this->createUser();

        $this->actingAs($user)->post('/meal-planner/food', [
            'food_id' => $food['id'],
            'slot' => 'makan_siang',
        ]);

        $this->actingAs($user)
            ->delete('/meal-planner/slot', ['slot' => 'makan_siang'])
            ->assertRedirect(route('meal-planner.index'));

        $this->assertDatabaseCount('meal_plans', 0);
    }

    public function test_meal_planner_hanya_menampilkan_milik_pengguna_sendiri(): void
    {
        $food = $this->createFood(['name' => 'Menu Orang Lain']);
        $lain = $this->createUser('Orang Lain', 'lain@example.com');

        $this->actingAs($lain)->post('/meal-planner/food', [
            'food_id' => $food['id'],
            'slot' => 'sarapan',
        ]);

        $saya = $this->createUser('Saya', 'saya@example.com');

        // Slot pengguna lain tidak boleh ikut tampil pada akun ini.
        $this->actingAs($saya)
            ->get('/meal-planner')
            ->assertOk()
            ->assertSee('Belum ada makanan');

        $this->assertDatabaseMissing('meal_plans', ['user_id' => $saya->getAuthIdentifier()]);
    }
}
