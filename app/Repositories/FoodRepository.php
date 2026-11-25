<?php

namespace App\Repositories;

use App\Database\Connection;
use App\Support\FoodSort;
use PDO;

/**
 * Akses data tabel `foods`.
 *
 * Primary key berupa UUID string, sehingga tidak memakai auto-increment.
 */
final class FoodRepository
{
    private function db(): PDO
    {
        return Connection::pdo();
    }

    /**
     * Daftar makanan dengan filter pencarian, kategori, dan pengurutan.
     *
     * @param  array{q?: ?string, category?: ?string, sort?: ?string}  $filters
     * @return list<array>
     */
    public function all(array $filters = []): array
    {
        $query = 'SELECT * FROM foods WHERE 1 = 1';
        $params = [];

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $query .= ' AND name LIKE ?';
            $params[] = '%'.$keyword.'%';
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $query .= ' AND category = ?';
            $params[] = $category;
        }

        $query .= ' '.FoodSort::orderBy((string) ($filters['sort'] ?? ''));

        $stmt = $this->db()->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $stmt = $this->db()->prepare('SELECT * FROM foods WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Data ringan untuk mesin rekomendasi.
     *
     * @return list<array{id: string, name: string, category: string, price: float, calories: float, protein: float, carbs: float, fat: float}>
     */
    public function allForRecommendation(): array
    {
        $stmt = $this->db()->query(
            'SELECT id, name, category, price, calories, protein, carbs, fat FROM foods'
        );

        return $stmt->fetchAll();
    }

    /**
     * Simpan makanan baru dan kembalikan UUID-nya.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): string
    {
        $id = $this->generateUuid();

        $stmt = $this->db()->prepare(
            'INSERT INTO foods
                (id, name, category, price, portion_description, description,
                 calories, protein, carbs, fat, image_url, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $id,
            $data['name'],
            $data['category'],
            $data['price'],
            $data['portion_description'],
            $data['description'] ?? null,
            $data['calories'],
            $data['protein'],
            $data['carbs'],
            $data['fat'],
            $data['image_url'] ?? null,
        ]);

        return $id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE foods SET
                name = ?, category = ?, price = ?, portion_description = ?,
                description = ?, calories = ?, protein = ?, carbs = ?, fat = ?,
                image_url = ?, updated_at = NOW()
             WHERE id = ?'
        );

        $stmt->execute([
            $data['name'],
            $data['category'],
            $data['price'],
            $data['portion_description'],
            $data['description'] ?? null,
            $data['calories'],
            $data['protein'],
            $data['carbs'],
            $data['fat'],
            $data['image_url'] ?? null,
            $id,
        ]);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db()->prepare('DELETE FROM foods WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * UUID v4 sederhana untuk primary key foods.
     */
    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
