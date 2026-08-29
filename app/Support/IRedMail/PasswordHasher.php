<?php

namespace App\Support\IRedMail;

use InvalidArgumentException;
use RuntimeException;

/**
 * Hashes and verifies passwords using the schemes iRedMail stores in
 * `mailbox.password` and `admin.password`.
 *
 * Hashes are prefixed with the scheme name in braces, e.g.
 * `{SSHA512}<base64>` or `{SHA512-CRYPT}$6$<salt>$<hash>`.
 */
class PasswordHasher
{
    public const SSHA512 = 'SSHA512';

    public const SHA512_CRYPT = 'SHA512-CRYPT';

    /** Salt length in bytes used by iRedMail for the salted SHA schemes. */
    private const SSHA_SALT_BYTES = 8;

    /**
     * @return list<string>
     */
    public static function supportedSchemes(): array
    {
        return [self::SSHA512, self::SHA512_CRYPT];
    }

    public function make(string $password, string $scheme = self::SSHA512): string
    {
        return match (strtoupper($scheme)) {
            self::SSHA512 => $this->makeSsha512($password),
            self::SHA512_CRYPT => $this->makeSha512Crypt($password),
            default => throw new InvalidArgumentException("Unsupported password scheme [{$scheme}]."),
        };
    }

    /**
     * Re-hash a password using the same scheme the account already uses, so
     * updating a password never silently migrates it to another scheme.
     */
    public function makeMatching(string $password, ?string $existingHash): string
    {
        $scheme = $existingHash !== null ? $this->schemeOf($existingHash) : null;

        return $this->make($password, $scheme ?? self::SSHA512);
    }

    public function verify(string $password, string $hash): bool
    {
        $scheme = $this->schemeOf($hash);

        if ($scheme === null) {
            return false;
        }

        $payload = substr($hash, strlen($scheme) + 2);

        return match ($scheme) {
            self::SSHA512 => $this->verifySsha512($password, $payload),
            self::SHA512_CRYPT => $this->verifyCrypt($password, $payload),
            default => false,
        };
    }

    /**
     * Extract the scheme name from a `{SCHEME}payload` hash, or null when the
     * hash carries no recognisable prefix.
     */
    public function schemeOf(string $hash): ?string
    {
        if (! preg_match('/^\{([A-Za-z0-9-]+)\}/', $hash, $matches)) {
            return null;
        }

        return strtoupper($matches[1]);
    }

    /** `{SSHA512}` + base64(sha512(password . salt) . salt) */
    private function makeSsha512(string $password): string
    {
        $salt = random_bytes(self::SSHA_SALT_BYTES);
        $digest = hash('sha512', $password.$salt, true);

        return '{'.self::SSHA512.'}'.base64_encode($digest.$salt);
    }

    private function verifySsha512(string $password, string $payload): bool
    {
        $decoded = base64_decode($payload, true);

        // 64 bytes of SHA-512 digest followed by the salt.
        if ($decoded === false || strlen($decoded) <= 64) {
            return false;
        }

        $digest = substr($decoded, 0, 64);
        $salt = substr($decoded, 64);

        return hash_equals($digest, hash('sha512', $password.$salt, true));
    }

    /** `{SHA512-CRYPT}` + crypt() output using the `$6$` (SHA-512) method. */
    private function makeSha512Crypt(string $password): string
    {
        $salt = '$6$'.$this->cryptSalt(16);
        $hash = crypt($password, $salt);

        // crypt() returns a short failure string ("*0"/"*1") rather than throwing.
        if (strlen($hash) < 13) {
            throw new RuntimeException('crypt() failed to produce a SHA512-CRYPT hash.');
        }

        return '{'.self::SHA512_CRYPT.'}'.$hash;
    }

    private function verifyCrypt(string $password, string $payload): bool
    {
        $computed = crypt($password, $payload);

        return strlen($computed) >= 13 && hash_equals($payload, $computed);
    }

    private function cryptSalt(int $length): string
    {
        $alphabet = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $salt = '';

        for ($i = 0; $i < $length; $i++) {
            $salt .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $salt;
    }
}
