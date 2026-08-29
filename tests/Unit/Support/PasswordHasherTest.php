<?php

namespace Tests\Unit\Support;

use App\Support\IRedMail\PasswordHasher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hasher = new PasswordHasher;
    }

    public static function schemes(): array
    {
        return [
            'ssha512' => [PasswordHasher::SSHA512, 105],
            'sha512-crypt' => [PasswordHasher::SHA512_CRYPT, 120],
        ];
    }

    #[DataProvider('schemes')]
    public function test_it_produces_hashes_the_same_length_as_iredmail(string $scheme, int $length): void
    {
        $this->assertSame($length, strlen($this->hasher->make('s3cret', $scheme)));
    }

    #[DataProvider('schemes')]
    public function test_it_verifies_the_password_it_hashed(string $scheme): void
    {
        $hash = $this->hasher->make('s3cret', $scheme);

        $this->assertTrue($this->hasher->verify('s3cret', $hash));
        $this->assertFalse($this->hasher->verify('S3CRET', $hash));
        $this->assertFalse($this->hasher->verify('', $hash));
    }

    #[DataProvider('schemes')]
    public function test_it_salts_each_hash_separately(string $scheme): void
    {
        $this->assertNotSame(
            $this->hasher->make('s3cret', $scheme),
            $this->hasher->make('s3cret', $scheme),
        );
    }

    /**
     * Older iRedMail releases used a 5-byte salt instead of 8. Those rows are
     * still present in production and must keep verifying.
     */
    public function test_it_verifies_ssha512_hashes_with_a_shorter_salt(): void
    {
        $salt = 'abcde';
        $hash = '{SSHA512}'.base64_encode(hash('sha512', 'legacy-pass'.$salt, true).$salt);

        $this->assertSame(101, strlen($hash));
        $this->assertTrue($this->hasher->verify('legacy-pass', $hash));
        $this->assertFalse($this->hasher->verify('other', $hash));
    }

    public function test_it_keeps_the_existing_scheme_when_changing_a_password(): void
    {
        $existing = $this->hasher->make('old', PasswordHasher::SHA512_CRYPT);

        $rehashed = $this->hasher->makeMatching('new', $existing);

        $this->assertSame(PasswordHasher::SHA512_CRYPT, $this->hasher->schemeOf($rehashed));
        $this->assertTrue($this->hasher->verify('new', $rehashed));
    }

    public function test_it_defaults_to_ssha512_for_an_unknown_existing_hash(): void
    {
        $this->assertSame(
            PasswordHasher::SSHA512,
            $this->hasher->schemeOf($this->hasher->makeMatching('new', null)),
        );
    }

    public function test_it_rejects_hashes_it_cannot_recognise(): void
    {
        $this->assertNull($this->hasher->schemeOf('not-a-hash'));
        $this->assertFalse($this->hasher->verify('anything', 'not-a-hash'));
        $this->assertFalse($this->hasher->verify('anything', '{MD5}abc'));
    }
}
