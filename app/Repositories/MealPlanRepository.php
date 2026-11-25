<?php

namespace App\Repositories;

use App\Database\Connection;
use App\Support\MealSlot;
use PDO;

/**
 * Akses data tabel `meal_plans`.
 *
 * Nilai gizi & harga disimpan per baris (denormalisasi) supaya rencana makan
 * yang sudah dibuat tetap stabil meski data makanan berubah.
 */
final class MealPlanRepository
{
    private function db(): PDO
    {
        return Connection::pdo();
    }

    /**
     * Ringkasan rencana makan satu hari, dikelompokkan per slot.
     *
     * @return array{
     *     date: string,
     *     slots: array<string, array{key: string, label: string, items: list<array>, calories: float, summary: string}>,
     *     totals: array{calories: float, protein: float, carbs: float, fat: float, price: float},
     *     count: int
     * }
     */
    public function dailySummary(int $userId, ?string $date = null): array
    {
        $date = $date ?: today()->toDateString();

        $stmt = $this->db()->prepare(
            'SELECT * FROM meal_plans
             WHERE user_id = ? AND entry_date = ?
             ORDER BY id ASC'
        );
        $stmt->execute([$userId, $date]);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(string) $row['meal_slot']][] = $row;
        }

        $totals = ['calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0, 'price' => 0.0];
        $slots = [];
        $count = 0;

        foreach (MealSlot::all() as $key => $label) {
            $items = $grouped[$key] ?? [];
            $slotCalories = 0.0;
            $names = [];

            foreach ($items as $item) {
                $slotCalories += (float) $item['calories'];
                $names[] = (string) $item['label'];

                $totals['calories'] += (float) $item['calories'];
                $totals['protein'] += (float) $item['protein'];
                $totals['carbs'] += (float) $item['carbs'];
                $totals['fat'] += (float) $item['fat'];
                $totals['price'] += (float) $item['price'];
            }

            $slots[$key] = [
                'key' => $key,
                'label' => $label,
                'items' => $items,
                'calories' => $slotCalories,
                'summary' => implode(' · ', $names),
            ];
            $count += count($items);
        }

        return [
            'date' => $date,
            'slots' => $slots,
            'totals' => $totals,
            'count' => $count,
        ];
    }

    /**
     * Simpan satu baris menu ke Meal Planner.
     *
     * @param  array{
     *     user_id: int, meal_slot: string, label: string, food_id?: ?string,
     *     entry_date?: ?string, calories?: float, protein?: float,
     *     carbs?: float, fat?: float, price?: float
     * }  $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO meal_plans
                (user_id, entry_date, meal_slot, label, food_id,
                 calories, protein, carbs, fat, price, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $data['user_id'],
            $data['entry_date'] ?? today()->toDateString(),
            $data['meal_slot'],
            $data['label'],
            $data['food_id'] ?? null,
            (float) ($data['calories'] ?? 0),
            (float) ($data['protein'] ?? 0),
            (float) ($data['carbs'] ?? 0),
            (float) ($data['fat'] ?? 0),
            (float) ($data['price'] ?? 0),
        ]);

        return (int) $this->db()->lastInsertId();
    }

    /**
     * Kosongkan seluruh item pada satu slot makan.
     */
    public function clearSlot(int $userId, string $slot, ?string $date = null): void
    {
        $stmt = $this->db()->prepare(
            'DELETE FROM meal_plans WHERE user_id = ? AND meal_slot = ? AND entry_date = ?'
        );
        $stmt->execute([$userId, $slot, $date ?: today()->toDateString()]);
    }

    /**
     * Peta food_id yang sudah ada per slot pada satu tanggal.
     *
     * @return array<string, array<string, true>>
     */
    public function existingFoodIdsBySlot(int $userId, ?string $date = null): array
    {
        $stmt = $this->db()->prepare(
            'SELECT meal_slot, food_id FROM meal_plans
             WHERE user_id = ? AND entry_date = ? AND food_id IS NOT NULL'
        );
        $stmt->execute([$userId, $date ?: today()->toDateString()]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(string) $row['meal_slot']][(string) $row['food_id']] = true;
        }

        return $map;
    }

    /**
     * Jumlah item menu pengguna pada satu tanggal (default hari ini).
     */
    public function countForDate(int $userId, ?string $date = null): int
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM meal_plans WHERE user_id = ? AND entry_date = ?'
        );
        $stmt->execute([$userId, $date ?: today()->toDateString()]);

        return (int) $stmt->fetchColumn();
    }
}