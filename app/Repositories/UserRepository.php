<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Akses data tabel `users` (beserta profil yang menempel).
 *
 * Seluruh query memakai prepared statement; tidak ada input pengguna yang
 * disisipkan langsung ke string SQL.
 */
final class UserRepository
{
    private function db(): PDO
    {
        return Connection::pdo();
    }

    /**
     * Query dasar user + full_name dari tabel profiles.
     */
    private function baseSelect(): string
    {
        return 'SELECT u.*, p.full_name
                FROM users u
                LEFT JOIN profiles p ON p.user_id = u.id';
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare($this->baseSelect().' WHERE u.id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare($this->baseSelect().' WHERE u.email = ? LIMIT 1');
        $stmt->execute([$email]);

        return $stmt->fetch() ?: null;
    }

    public function findByRememberToken(int $id, string $token): ?array
    {
        $stmt = $this->db()->prepare($this->baseSelect().' WHERE u.id = ? AND u.remember_token = ? LIMIT 1');
        $stmt->execute([$id, $token]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Buat akun baru beserta profil kosongnya dalam satu transaksi.
     */
    public function create(string $name, string $email, string $hashedPassword): int
    {
        return Connection::transaction(function (PDO $db) use ($name, $email, $hashedPassword): int {
            $stmt = $db->prepare(
                'INSERT INTO users (name, email, password, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$name, $email, $hashedPassword]);

            $userId = (int) $db->lastInsertId();

            $profile = $db->prepare(
                'INSERT INTO profiles (user_id, full_name, created_at, updated_at)
                 VALUES (?, ?, NOW(), NOW())'
            );
            $profile->execute([$userId, $name]);

            return $userId;
        });
    }

    public function updateName(int $id, string $name): void
    {
        $stmt = $this->db()->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        $stmt = $this->db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$hashedPassword, $id]);
    }

    public function updateRememberToken(int $id, ?string $token): void
    {
        $stmt = $this->db()->prepare('UPDATE users SET remember_token = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$token, $id]);
    }
}
