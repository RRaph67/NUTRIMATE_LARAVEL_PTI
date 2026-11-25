<?php

use App\Support\Number;

if (! function_exists('fmt_num')) {
    /**
     * Format angka gaya Indonesia (lihat App\Support\Number).
     */
    function fmt_num(float|int|string|null $value, int $decimals = 1): string
    {
        return Number::format($value, $decimals);
    }
}

if (! function_exists('rupiah')) {
    /**
     * Format nilai menjadi "Rp 25.000".
     */
    function rupiah(float|int|string|null $value): string
    {
        return Number::rupiah($value);
    }
}

if (! function_exists('initials_of')) {
    /**
     * Inisial satu huruf dari sebuah nama, untuk avatar.
     */
    function initials_of(?string $name): string
    {
        $name = trim((string) $name);

        return mb_strtoupper(mb_substr($name !== '' ? $name : 'U', 0, 1));
    }
}
