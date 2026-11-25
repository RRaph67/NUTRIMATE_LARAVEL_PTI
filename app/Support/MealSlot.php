<?php

namespace App\Support;

/**
 * Slot makan yang dipakai Meal Planner, rekomendasi, dan kalkulator.
 */
final class MealSlot
{
    public const BREAKFAST = 'sarapan';

    public const LUNCH = 'makan_siang';

    public const DINNER = 'makan_malam';

    /**
     * Semua slot: key => label.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            self::BREAKFAST => 'Sarapan',
            self::LUNCH => 'Makan Siang',
            self::DINNER => 'Makan Malam',
        ];
    }

    public static function isValid(?string $slot): bool
    {
        return array_key_exists((string) $slot, self::all());
    }

    public static function label(string $slot): string
    {
        return self::all()[$slot] ?? $slot;
    }
}
