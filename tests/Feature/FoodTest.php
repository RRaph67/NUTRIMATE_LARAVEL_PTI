<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_menampilkan_daftar_makanan(): void
    {
        $this->createFood(['name' => 'Nasi Uduk Warteg']);
        $this->createFood(['name' => 'Tempe Orek', 'category' => 'Protein']);

        $this->actingAs($this->createUser())
            ->get('/foods')
            ->assertOk()
            ->assertSee('Nasi Uduk Warteg')
            ->assertSee('Tempe Orek');
    }

    public function test_katalog_dapat_difilter_kategori_dan_pencarian(): void
    {
        $this->createFood(['name' => 'Nasi Uduk Warteg']);
        $this->createFood(['name' => 'Tempe Orek', 'category' => 'Protein']);

        $user = $this->createUser();

        $this->actingAs($user)
            ->get('/foods?category=Protein')
            ->assertOk()
            ->assertSee('Tempe Orek')
            ->assertDontSee('Nasi Uduk Warteg');

        $this->actingAs($user)
            ->get('/foods?q=uduk')
            ->assertOk()
            ->assertSee('Nasi Uduk Warteg')
            ->assertDontSee('Tempe Orek');
    }

    public function test_pengguna_dapat_menambah_makanan_baru(): void
    {
        $response = $this->actingAs($this->createUser())->post('/foods', [
            'name' => 'Ayam Bakar Madu',
            'category' => 'Protein',
            'price' => 15000,
            'portion_description' => '1 paha',
            'description' => 'Ayam bakar bumbu madu.',
            'calories' => 320,
            'protein' => 28,
            'carbs' => 12,
            'fat' => 18,
            'image_url' => 'https://contoh.test/ayam.jpg',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('foods', [
            'name' => 'Ayam Bakar Madu',
            'category' => 'Protein',
            'price' => 15000,
        ]);
    }

    public function test_validasi_menolak_kategori_tidak_dikenal_dan_angka_negatif(): void
    {
        $response = $this->actingAs($this->createUser())->post('/foods', [
            'name' => 'Makanan Aneh',
            'category' => 'Bukan Kategori',
            'price' => -5000,
            'portion_description' => '1 porsi',
            'calories' => -10,
            'protein' => 5,
            'carbs' => 5,
            'fat' => 5,
            'image_url' => 'bukan-url',
        ]);

        $response->assertSessionHasErrors(['category', 'price', 'calories', 'image_url']);
        $this->assertDatabaseMissing('foods', ['name' => 'Makanan Aneh']);
    }

    public function test_pengguna_dapat_memperbarui_makanan(): void
    {
        $food = $this->createFood(['name' => 'Nasi Uduk']);

        $response = $this->actingAs($this->createUser())->put("/foods/{$food['id']}", [
            'name' => 'Nasi Uduk Spesial',
            'category' => 'Karbohidrat',
            'price' => 9000,
            'portion_description' => '1 porsi',
            'calories' => 350,
            'protein' => 9,
            'carbs' => 60,
            'fat' => 6,
        ]);

        $response->assertRedirect(route('foods.show', $food['id']));
        $this->assertDatabaseHas('foods', [
            'id' => $food['id'],
            'name' => 'Nasi Uduk Spesial',
            'price' => 9000,
        ]);
    }

    public function test_pengguna_dapat_menghapus_makanan_lewat_post(): void
    {
        $food = $this->createFood();

        $this->actingAs($this->createUser())
            ->delete("/foods/{$food['id']}")
            ->assertRedirect(route('foods.index'));

        $this->assertDatabaseMissing('foods', ['id' => $food['id']]);
    }

    public function test_hapus_makanan_tidak_bisa_lewat_get(): void
    {
        $food = $this->createFood();

        $this->actingAs($this->createUser())
            ->get("/foods/{$food['id']}/delete")
            ->assertNotFound();

        $this->assertDatabaseHas('foods', ['id' => $food['id']]);
    }

    public function test_detail_makanan_tidak_ditemukan_diarahkan_ke_katalog(): void
    {
        $this->actingAs($this->createUser())
            ->get('/foods/id-yang-tidak-ada')
            ->assertRedirect(route('foods.index'))
            ->assertSessionHas('error');
    }
}
