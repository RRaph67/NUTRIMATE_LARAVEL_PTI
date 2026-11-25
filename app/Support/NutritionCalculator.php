<?php

namespace App\Support;

/**
 * Perhitungan BMR, TDEE, IMT, dan pembagian makronutrisi.
 *
 * Murni fungsi perhitungan tanpa akses database, sehingga mudah diuji dan
 * dipakai ulang (halaman kalkulator, endpoint, maupun view).
 */
final class NutritionCalculator
{
    /**
     * Faktor aktivitas fisik: nilai => label.
     *
     * @var array<string, string>
     */
    public const ACTIVITY_LEVELS = [
        '1.2' => 'Ringan (kuliah / rebahan santai)',
        '1.375' => 'Sedang (olahraga 1-3 hari/minggu)',
        '1.55' => 'Aktif (olahraga 3-5 hari/minggu)',
        '1.725' => 'Sangat Aktif (olahraga berat rutin)',
    ];

    /**
     * Distribusi energi makronutrisi (persen kalori harian).
     *
     * @var array{protein: float, carbs: float, fat: float}
     */
    public const MACRO_SPLIT = [
        'protein' => 0.15,
        'carbs' => 0.55,
        'fat' => 0.30,
    ];

    /** Kalori per gram protein & karbohidrat. */
    private const KCAL_PER_GRAM_CARB = 4;

    /** Kalori per gram lemak. */
    private const KCAL_PER_GRAM_FAT = 9;

    /**
     * Hitung BMR (Mifflin-St Jeor), TDEE, IMT, dan makronutrisi.
     *
     * @param  array{gender?: string, age?: int|float, weight?: int|float, height?: int|float, activity?: string|float}  $input
     * @return array{
     *     gender: string, age: int, weight: float, height: float,
     *     activity_factor: float, activity_label: string,
     *     bmr: int, tdee: int, protein: int, carbs: int, fat: int,
     *     bmi: float, bmi_category: string
     * }
     */
    public static function calculate(array $input): array
    {
        $gender = ($input['gender'] ?? 'male') === 'female' ? 'female' : 'male';
        $age = (int) ($input['age'] ?? 20);
        $weight = (float) ($input['weight'] ?? 60);
        $height = (float) ($input['height'] ?? 170);

        $activityKey = self::normalizeActivity($input['activity'] ?? null);
        $activity = (float) $activityKey;

        // Mifflin-St Jeor:
        //   Pria   : 10*BB + 6.25*TB - 5*Usia + 5
        //   Wanita : 10*BB + 6.25*TB - 5*Usia - 161
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age);
        $bmr = $gender === 'male' ? $bmr + 5 : $bmr - 161;
        $bmr = max(0.0, $bmr);

        $tdee = $bmr * $activity;

        $protein = ($tdee * self::MACRO_SPLIT['protein']) / self::KCAL_PER_GRAM_CARB;
        $carbs = ($tdee * self::MACRO_SPLIT['carbs']) / self::KCAL_PER_GRAM_CARB;
        $fat = ($tdee * self::MACRO_SPLIT['fat']) / self::KCAL_PER_GRAM_FAT;

        $bmi = self::bmi($weight, $height);

        return [
            'gender' => $gender,
            'age' => $age,
            'weight' => $weight,
            'height' => $height,
            'activity_factor' => $activity,
            'activity_label' => self::ACTIVITY_LEVELS[$activityKey],
            'bmr' => (int) round($bmr),
            'tdee' => (int) round($tdee),
            'protein' => (int) round($protein),
            'carbs' => (int) round($carbs),
            'fat' => (int) round($fat),
            'bmi' => $bmi,
            'bmi_category' => self::bmiCategory($bmi),
        ];
    }

    /**
     * IMT = berat (kg) / tinggi (m)^2.
     */
    public static function bmi(float $weight, float $heightCm): float
    {
        $heightM = $heightCm / 100;

        if ($heightM <= 0) {
            return 0.0;
        }

        return round($weight / ($heightM * $heightM), 2);
    }

    /**
     * Kategori IMT menurut ambang batas Asia-Pasifik.
     */
    public static function bmiCategory(float $bmi): string
    {
        return match (true) {
            $bmi <= 0 => 'Tidak diketahui',
            $bmi < 18.5 => 'Kurang berat badan',
            $bmi < 23.0 => 'Berat badan ideal',
            $bmi < 25.0 => 'Berisiko kelebihan',
            $bmi < 30.0 => 'Obesitas I',
            default => 'Obesitas II',
        };
    }

    /**
     * Pastikan faktor aktivitas termasuk daftar yang dikenal.
     */
    public static function normalizeActivity(string|float|null $activity): string
    {
        $key = (string) $activity;

        if (array_key_exists($key, self::ACTIVITY_LEVELS)) {
            return $key;
        }

        // Terima bentuk numerik (mis. 1.3750) lalu cari padanan terdekat.
        $numeric = (float) $activity;
        foreach (array_keys(self::ACTIVITY_LEVELS) as $factor) {
            if (abs((float) $factor - $numeric) < 0.0005) {
                return $factor;
            }
        }

        return '1.2';
    }

    public static function genderLabel(string $gender): string
    {
        return $gender === 'female' ? 'Perempuan' : 'Laki-laki';
    }
}
