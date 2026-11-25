<?php

namespace App\Support;

/**
 * Helper format angka & teks Bahasa Indonesia.
 *
 * Dipakai lewat facade/helper `Nutri::number()` dan helper global `fmt_num()`,
 * sehingga format angka konsisten di seluruh view.
 */
final class Number
{
    /**
     * Format angka gaya Indonesia: 2100 -> "2.100", 21.80 -> "21,8".
     *
     * Nol di belakang koma dipangkas agar tampilan lebih ringkas.
     */
    public static function format(float|int|string|null $value, int $decimals = 1): string
    {
        $formatted = number_format((float) $value, max(1, $decimals), ',', '.');
        $trimmed = rtrim(rtrim($formatted, '0'), ',');

        return $trimmed === '' ? '0' : $trimmed;
    }

    /**
     * Format rupiah tanpa desimal: 25000 -> "Rp 25.000".
     */
    public static function rupiah(float|int|string|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
