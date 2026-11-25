<?php

namespace App\Services;

use App\Repositories\FoodRepository;
use App\Repositories\MealPlanRepository;
use App\Support\MealSlot;

/**
 * Logika penambahan menu ke Meal Planner.
 *
 * Dipakai bersama oleh Food Database, Recommendation, dan Nutrition
 * Calculator supaya format label serta perhitungan gizi per porsi konsisten
 * dan tidak diduplikasi di setiap controller.
 */
final class MealPlanService
{
    public function __construct(
        private MealPlanRepository $mealPlans,
        private FoodRepository $foods,
    ) {}

    /**
     * Ringkasan menu hari ini untuk satu pengguna.
     */
    public function todaySummary(int $userId): array
    {
        return $this->mealPlans->dailySummary($userId);
    }

    public function todayCount(int $userId): int
    {
        return $this->mealPlans->countForDate($userId);
    }

    /**
     * Tambahkan satu makanan (dikalikan porsi) ke slot makan hari ini.
     *
     * @param  array  $food  Baris tabel foods.
     * @param  bool  $withPortionLabel  Tampilkan "· N porsi" pada label.
     */
    public function addFood(int $userId, array $food, string $slot, int $portions = 1, bool $withPortionLabel = true): void
    {
        $label = $withPortionLabel
            ? $food['name'].' · '.$portions.' porsi'
            : (string) $food['name'];

        $this->mealPlans->create([
            'user_id' => $userId,
            'meal_slot' => $slot,
            'label' => $label,
            'food_id' => $food['id'],
            'calories' => (float) $food['calories'] * $portions,
            'protein' => (float) $food['protein'] * $portions,
            'carbs' => (float) $food['carbs'] * $portions,
            'fat' => (float) $food['fat'] * $portions,
            'price' => (float) $food['price'] * $portions,
        ]);
    }

    /**
     * Kosongkan satu slot makan hari ini.
     */
    public function clearSlot(int $userId, string $slot): void
    {
        $this->mealPlans->clearSlot($userId, $slot);
    }

    /**
     * Terapkan payload rekomendasi ([{slot, food_id}, ...]) ke Meal Planner.
     *
     * Menu yang sudah ada pada slot yang sama dilewati agar paket yang sama
     * tidak menumpuk ketika tombol "Gunakan Rekomendasi" ditekan berulang.
     *
     * @param  list<array{slot?: string, food_id?: string}>  $items
     * @return array{added: int, skipped: int}
     */
    public function applyRecommendation(int $userId, array $items): array
    {
        $existing = $this->mealPlans->existingFoodIdsBySlot($userId);
        $added = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $slot = (string) ($item['slot'] ?? '');
            $foodId = (string) ($item['food_id'] ?? '');

            if (! MealSlot::isValid($slot) || $foodId === '') {
                continue;
            }

            $food = $this->foods->find($foodId);
            if ($food === null) {
                continue;
            }

            if (isset($existing[$slot][$food['id']])) {
                $skipped++;

                continue;
            }

            $this->addFood($userId, $food, $slot, 1, withPortionLabel: false);
            $existing[$slot][$food['id']] = true;
            $added++;
        }

        return ['added' => $added, 'skipped' => $skipped];
    }
}