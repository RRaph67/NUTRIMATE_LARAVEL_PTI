<?php

namespace Tests\Unit;

use App\Support\FoodSort;
use App\Support\MealSlot;
use App\Support\Number;
use App\Support\NutritionCalculator;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public function test_number_format_memakai_gaya_indonesia(): void
    {
        $this->assertSame('2.100', Number::format(2100, 0));
        $this->assertSame('21,8', Number::format(21.8, 1));
        $this->assertSame('Rp 25.000', Number::rupiah(25000));
    }

    public function test_bmr_dan_tdee_pria_memakai_mifflin_st_jeor(): void
    {
        $hasil = NutritionCalculator::calculate([
            'gender' => 'male',
            'age' => 20,
            'weight' => 60,
            'height' => 170,
            'activity' => '1.2',
        ]);

        // 10*60 + 6.25*170 - 5*20 + 5 = 1567,5 -> 1568 kkal
        $this->assertSame(1568, $hasil['bmr']);
        $this->assertSame(1881, $hasil['tdee']); // 1567,5 x 1,2 = 1881
        $this->assertSame('Ringan (kuliah / rebahan santai)', $hasil['activity_label']);
    }

    public function test_bmr_wanita_memakai_pengurang_161(): void
    {
        $hasil = NutritionCalculator::calculate([
            'gender' => 'female',
            'age' => 21,
            'weight' => 55,
            'height' => 160,
            'activity' => '1.375',
        ]);

        // 10*55 + 6.25*160 - 5*21 - 161 = 1284 kkal
        $this->assertSame(1284, $hasil['bmr']);
        $this->assertSame(1766, $hasil['tdee']); // 1284 x 1,375
    }

    public function test_kategori_imt_memakai_ambang_asia_pasifik(): void
    {
        $this->assertSame('Kurang berat badan', NutritionCalculator::bmiCategory(18.4));
        $this->assertSame('Berat badan ideal', NutritionCalculator::bmiCategory(22.9));
        $this->assertSame('Berisiko kelebihan', NutritionCalculator::bmiCategory(24.5));
        $this->assertSame('Obesitas I', NutritionCalculator::bmiCategory(28.0));
        $this->assertSame('Obesitas II', NutritionCalculator::bmiCategory(31.0));
    }

    public function test_faktor_aktivitas_tidak_dikenal_kembali_ke_nilai_terendah(): void
    {
        $this->assertSame('1.2', NutritionCalculator::normalizeActivity('99'));
        $this->assertSame('1.55', NutritionCalculator::normalizeActivity('1.550'));
        $this->assertSame('1.2', NutritionCalculator::normalizeActivity(null));
    }

    public function test_pengurutan_hanya_menerima_opsi_yang_dikenal(): void
    {
        $this->assertSame(FoodSort::CHEAPEST, FoodSort::normalize('tidak-ada'));
        $this->assertSame('ORDER BY price ASC, name ASC', FoodSort::orderBy('cheap'));
        $this->assertSame('ORDER BY calories DESC, name ASC', FoodSort::orderBy('kalori-tinggi'));
    }

    public function test_slot_makan_hanya_tiga_yang_sah(): void
    {
        $this->assertSame(['sarapan', 'makan_siang', 'makan_malam'], array_keys(MealSlot::all()));
        $this->assertTrue(MealSlot::isValid('makan_siang'));
        $this->assertFalse(MealSlot::isValid('ngemil'));
        $this->assertSame('Makan Malam', MealSlot::label('makan_malam'));
    }
}
