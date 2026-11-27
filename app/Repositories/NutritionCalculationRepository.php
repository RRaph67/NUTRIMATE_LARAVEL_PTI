<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Akses data tabel `nutrition_calculations` (riwayat perhitungan gizi).
 */
final class NutritionCalculationRepository
{
    private function db(): PDO
    {
        return Connection::pdo();
    }

    /**
     * Simpan hasil perhitungan dan kembalikan ID barisnya.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(int $userId, array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO nutrition_calculations
                (user_id, gender, age, weight, height,
                 activity_factor, activity_label,
                 bmr, tdee, protein, carbs, fat, bmi, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $userId,
            $data['gender'],
            (int) $data['age'],
            (float) $data['weight'],
            (float) $data['height'],
            (float) $data['activity_factor'],
            $data['activity_label'],
            (float) $data['bmr'],
            (float) $data['tdee'],
            (float) $data['protein'],
            (float) $data['carbs'],
            (float) $data['fat'],
            (float) $data['bmi'],
        ]);

        return (int) $this->db()->lastInsertId();
    }

    /**
     * Riwayat perhitungan pengguna, terbaru lebih dulu.
     *
     * @return list<array>
     */
    public function historyForUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        // LIMIT tidak bisa di-bind sebagai parameter pada MySQL/MariaDB,
        // jadi nilainya sudah dibatasi & di-cast menjadi integer di atas.
        $stmt = $this->db()->prepare(
            'SELECT * FROM nutrition_calculations
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT '.$limit
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function latestForUser(int $userId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM nutrition_calculations
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Hapus satu baris riwayat milik pengguna.
     *
     * @return bool true bila ada baris yang benar-benar terhapus
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        $stmt = $this->db()->prepare(
            'DELETE FROM nutrition_calculations WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function clearForUser(int $userId): void
    {
        $stmt = $this->db()->prepare('DELETE FROM nutrition_calculations WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}