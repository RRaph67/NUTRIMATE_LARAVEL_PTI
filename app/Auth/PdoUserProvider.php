<?php

namespace App\Auth;

use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * User provider berbasis PDO untuk guard `web`.
 *
 * Laravel tetap menangani sesi, regenerasi session, dan "remember me";
 * kelas ini hanya menjembatani tabel `users` ke kontrak Auth Laravel.
 */
final class PdoUserProvider implements UserProvider
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        $row = $this->users->findById((int) $identifier);

        return $row ? PdoUser::fromRow($row) : null;
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        $row = $this->users->findByRememberToken((int) $identifier, (string) $token);

        return $row ? PdoUser::fromRow($row) : null;
    }

    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        $this->users->updateRememberToken((int) $user->getAuthIdentifier(), $token);
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if (empty($credentials['email'])) {
            return null;
        }

        $row = $this->users->findByEmail((string) $credentials['email']);

        return $row ? PdoUser::fromRow($row) : null;
    }

    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return $this->hasher->check((string) ($credentials['password'] ?? ''), $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        #[\SensitiveParameter] array $credentials,
        bool $force = false,
    ): void {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $this->users->updatePassword(
            (int) $user->getAuthIdentifier(),
            $this->hasher->make((string) ($credentials['password'] ?? '')),
        );
    }
}
