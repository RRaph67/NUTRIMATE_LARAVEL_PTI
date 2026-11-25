<?php

namespace App\Services;

use App\Repositories\FoodRepository;

/**
 * Penyusun menu sementara di Nutrition Calculator.
 *
 * Pilihan bahan makanan disimpan di session dengan format
 * `food_id => porsi`. Array ber-key food_id membuat satu makanan tidak bisa
 * masuk dua kali; menambah ulang hanya memperbarui porsinya.
 */
final class MenuBuilder
{
    /** Batas porsi per makanan (sama dengan Meal Planner). */
    public const MAX_PORTIONS = 2;

    private const SESSION_KEY = 'calculator.items';

    public function __construct(private FoodRepository $foods) {}

    /**
     * Tambah atau perbarui porsi satu makanan.
     *
     * @return bool true bila porsi diperbarui (makanan sudah ada)
     */
    public function add(string $foodId, int $portions): bool
    {
        $items = $this->raw();
        $isUpdate = isset($items[$foodId]);

        $items[$foodId] = $this->clampPortions($portions);
        session([self::SESSION_KEY => $items]);

        return $isUpdate;
    }

    public function remove(string $foodId): bool
    {
        $items = $this->raw();
        if (! isset($items[$foodId])) {
            return false;
        }

        unset($items[$foodId]);
        session([self::SESSION_KEY => $items]);

        return true;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Rincian menu terpilih beserta total gizinya.
     *
     * Makanan yang sudah dihapus dari database otomatis dibuang dari session.
     *
     * @return array{
     *     items: list<array{food: array, portions: int, calories: float, protein: float, carbs: float, fat: float, price: float}>,
     *     totals: array{calories: float, protein: float, carbs: float, fat: float, price: float, portions: int}
     * }
     */
    public function selection(): array
    {
        $items = [];
        $totals = [
            'calories' => 0.0,
            'protein' => 0.0,
            'carbs' => 0.0,
            'fat' => 0.0,
            'price' => 0.0,
            'portions' => 0,
        ];

        $raw = $this->raw();
        $dirty = false;

        foreach ($raw as $foodId => $portions) {
            $food = $this->foods->find((string) $foodId);

            if ($food === null) {
                unset($raw[$foodId]);
                $dirty = true;

                continue;
            }

            $portions = $this->clampPortions((int) $portions);

            $row = [
                'food' => $food,
                'portions' => $portions,
                'calories' => (float) $food['calories'] * $portions,
                'protein' => (float) $food['protein'] * $portions,
                'carbs' => (float) $food['carbs'] * $portions,
                'fat' => (float) $food['fat'] * $portions,
                'price' => (float) $food['price'] * $portions,
            ];

            $items[] = $row;

            $totals['calories'] += $row['calories'];
            $totals['protein'] += $row['protein'];
            $totals['carbs'] += $row['carbs'];
            $totals['fat'] += $row['fat'];
            $totals['price'] += $row['price'];
            $totals['portions'] += $portions;
        }

        if ($dirty) {
            session([self::SESSION_KEY => $raw]);
        }

        return ['items' => $items, 'totals' => $totals];
    }

    /**
     * Jumlah item terpilih (tanpa menyentuh database).
     */
    public function count(): int
    {
        return count($this->raw());
    }

    public function clampPortions(int $portions): int
    {
        return max(1, min(self::MAX_PORTIONS, $portions));
    }

    /**
     * @return array<string, int>
     */
    private function raw(): array
    {
        $raw = session(self::SESSION_KEY, []);

        return is_array($raw) ? $raw : [];
    }
}
