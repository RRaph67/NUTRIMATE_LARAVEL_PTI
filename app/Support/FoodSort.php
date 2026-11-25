<?php

namespace App\Support;

/**
 * Pengurutan katalog makanan.
 */
final class FoodSort
{
    public const CHEAPEST = 'cheap';

    public const MOST_EXPENSIVE = 'termahal';

    public const LOWEST_CALORIES = 'kalori-rendah';

    public const HIGHEST_CALORIES = 'kalori-tinggi';

    /**
     * Peta opsi urutan: nilai => [label, kolom SQL, arah].
     *
     * @return array<string, array{label: string, column: string, direction: string}>
     */
    public static function options(): array
    {
        return [
            self::CHEAPEST => ['label' => 'Termurah', 'column' => 'price', 'direction' => 'ASC'],
            self::LOWEST_CALORIES => ['label' => 'Kalori Rendah', 'column' => 'calories', 'direction' => 'ASC'],
            self::MOST_EXPENSIVE => ['label' => 'Termahal', 'column' => 'price', 'direction' => 'DESC'],
            self::HIGHEST_CALORIES => ['label' => 'Kalori Tertinggi', 'column' => 'calories', 'direction' => 'DESC'],
        ];
    }

    public static function normalize(?string $sort): string
    {
        return array_key_exists((string) $sort, self::options())
            ? (string) $sort
            : self::CHEAPEST;
    }

    /**
     * Klausa ORDER BY yang aman: kolom & arah hanya berasal dari konstanta
     * di atas, bukan dari input pengguna.
     */
    public static function orderBy(string $sort): string
    {
        $option = self::options()[self::normalize($sort)];

        return "ORDER BY {$option['column']} {$option['direction']}, name ASC";
    }
}
