<?php

namespace App\Auth;

use App\Models\Vmail\Mailbox;
use App\Support\IRedMail\PasswordHasher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Authenticates against iRedMail's `mailbox` table instead of a local users
 * table, so credentials stay in one place and never drift from the mail server.
 */
class IRedMailUserProvider implements UserProvider
{
    public function __construct(private readonly PasswordHasher $hasher) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->activeQuery()->whereKey($identifier)->first();
    }

    /** iRedMail has no column for remember tokens, so this is always a miss. */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // No column to persist it to.
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $username = $credentials['username'] ?? $credentials['email'] ?? null;

        if (! is_string($username) || $username === '') {
            return null;
        }

        return $this->activeQuery()->whereKey(Str::lower(trim($username)))->first();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        if (! is_string($password) || $password === '') {
            return false;
        }

        return $this->hasher->verify($password, $user->getAuthPassword());
    }

    /**
     * Passwords are only ever rehashed through an explicit password change --
     * a login must not silently rewrite a row in the mail server's database.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Intentionally a no-op.
    }

    /**
     * Expired and deactivated accounts cannot sign in, matching how Dovecot
     * treats them.
     */
    /**
     * @return Builder<Mailbox>
     */
    private function activeQuery(): Builder
    {
        return Mailbox::query()
            ->where('active', 1)
            ->where('expired', '>', now());
    }
}
