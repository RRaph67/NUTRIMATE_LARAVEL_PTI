<?php

namespace App\Support;

/**
 * Daftar kategori makanan yang sah.
 *
 * Dipakai untuk validasi server-side, filter katalog, dan dropdown form,
 * sehingga tidak ada daftar kategori yang terduplikasi di beberapa tempat.
 */
final class FoodCategory
{
    public const CARBOHYDRATE = 'Karbohidrat';

    public const PROTEIN = 'Protein';

    public const VEGETABLE = 'Sayuran';

    public const FRUIT = 'Buah';

    public const DRINK = 'Minuman';

    /**
     * Semua kategori dalam urutan tampil.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CARBOHYDRATE,
            self::PROTEIN,
            self::VEGETABLE,
            self::FRUIT,
            self::DRINK,
        ];
    }

    public static function isValid(?string $category): bool
    {
        return in_array($category, self::all(), true);
    }
}
