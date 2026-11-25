<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Representasi pengguna yang login tanpa Eloquent.
 *
 * Data berasal dari tabel `users` yang di-join dengan `profiles`, sehingga
 * nama tampilan (full_name) tersedia langsung pada objek ini.
 */
final class PdoUser implements Authenticatable
{
    public function __construct(private array $attributes = []) {}

    /**
     * Buat objek dari baris hasil query (users LEFT JOIN profiles).
     */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }

    /**
     * Ambil satu atribut mentah.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Nama tampilan: full_name dari profil, atau name dari akun.
     */
    public function displayName(): string
    {
        $name = trim((string) ($this->attributes['full_name'] ?? ''));

        return $name !== '' ? $name : (string) ($this->attributes['name'] ?? '');
    }

    /**
     * Inisial untuk avatar (satu huruf kapital).
     */
    public function initials(): string
    {
        $name = $this->displayName();

        return mb_strtoupper(mb_substr($name !== '' ? $name : 'U', 0, 1));
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->attributes['id'] ?? null;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->attributes['password'] ?? '');
    }

    public function getRememberToken(): ?string
    {
        return $this->attributes['remember_token'] ?? null;
    }

    public function setRememberToken($value): void
    {
        $this->attributes['remember_token'] = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
