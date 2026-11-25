<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Akses data tabel `profiles`.
 *
 * Profil menyimpan nama lengkap serta target gizi & budget harian yang
 * dipakai halaman Rekomendasi dan Meal Planner.
 */
final class ProfileRepository
{
    private function db(): PDO
    {
        return Connection::pdo();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM profiles WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: null;
    }

    public function updateFullName(int $userId, string $fullName): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE profiles SET full_name = ?, updated_at = NOW() WHERE user_id = ?'
        );
        $stmt->execute([$fullName, $userId]);
    }

    public function updateTargets(int $userId, int $calorieTarget, float $dailyBudget): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE profiles
             SET calorie_target = ?, daily_budget = ?, updated_at = NOW()
             WHERE user_id = ?'
        );
        $stmt->execute([$calorieTarget, $dailyBudget, $userId]);
    }

    /**
     * Target kalori efektif: dari profil, atau default aplikasi.
     */
    public function calorieTargetFor(int $userId, int $default = 2000): int
    {
        $profile = $this->findByUserId($userId);
        $target = (int) ($profile['calorie_target'] ?? 0);

        return $target > 0 ? $target : $default;
    }

    /**
     * Budget harian efektif: dari profil, atau default aplikasi.
     */
    public function dailyBudgetFor(int $userId, float $default = 30000): float
    {
        $profile = $this->findByUserId($userId);
        $budget = (float) ($profile['daily_budget'] ?? 0);

        return $budget > 0 ? $budget : $default;
    }
}