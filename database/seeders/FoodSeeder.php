<?php

namespace Database\Seeders;

use App\Database\Connection;
use Illuminate\Database\Seeder;

/**
 * Mengisi katalog makanan awal (36 menu) ke tabel `foods`.
 *
 * Seeder bersifat idempoten: baris dengan `id` yang sama akan diperbarui,
 * bukan diduplikasi, sehingga aman dijalankan berulang.
 */
class FoodSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $foods */
        $foods = require __DIR__.'/data/foods.php';

        $db = Connection::pdo();

        $statement = $db->prepare(
            'INSERT INTO foods
                (id, name, category, price, portion_description, description,
                 calories, protein, carbs, fat, image_url, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                category = VALUES(category),
                price = VALUES(price),
                portion_description = VALUES(portion_description),
                description = VALUES(description),
                calories = VALUES(calories),
                protein = VALUES(protein),
                carbs = VALUES(carbs),
                fat = VALUES(fat),
                image_url = VALUES(image_url),
                updated_at = NOW()'
        );

        $db->beginTransaction();

        try {
            foreach ($foods as $food) {
                $statement->execute([
                    $food['id'],
                    $food['name'],
                    $food['category'],
                    $food['price'],
                    $food['portion_description'],
                    $food['description'],
                    $food['calories'],
                    $food['protein'],
                    $food['carbs'],
                    $food['fat'],
                    $food['image_url'],
                ]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();

            throw $e;
        }

        $this->command?->info(count($foods).' data makanan berhasil di-seed.');
    }
}
