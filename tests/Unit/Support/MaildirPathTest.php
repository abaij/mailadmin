<?php

namespace Tests\Unit\Support;

use App\Support\IRedMail\MaildirPath;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MaildirPathTest extends TestCase
{
    private MaildirPath $paths;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paths = new MaildirPath;
    }

    /**
     * Both expectations are real rows copied from the production dump, so a
     * change in the algorithm shows up here immediately.
     */
    public function test_it_reproduces_paths_generated_by_iredmail(): void
    {
        $createdAt = new DateTimeImmutable('2025-12-26 02:43:37');

        $this->assertSame(
            'arenaloka.com/a/_/b/a.bayu-2025.12.26.02.43.37/',
            $this->paths->generate('a.bayu@arenaloka.com', $createdAt),
        );

        $this->assertSame(
            'arenaloka.com/c/s/_/cs-2026.01.01.05.03.12/',
            $this->paths->generate('cs@arenaloka.com', new DateTimeImmutable('2026-01-01 05:03:12')),
        );
    }

    public function test_it_pads_local_parts_shorter_than_the_hash_depth(): void
    {
        $this->assertSame(['a', '_', '_'], $this->paths->hashSegments('a'));
        $this->assertSame(['a', 'b', '_'], $this->paths->hashSegments('ab'));
        $this->assertSame(['a', 'b', 'c'], $this->paths->hashSegments('abcd'));
    }

    public function test_it_replaces_characters_that_are_not_alphanumeric(): void
    {
        $this->assertSame(['a', '_', 'b'], $this->paths->hashSegments('a.bayu'));
        $this->assertSame(['j', '_', 'd'], $this->paths->hashSegments('j-doe'));
        $this->assertSame(['a', 'b', 'c'], $this->paths->hashSegments('ABC'));
    }

    public function test_it_lowercases_the_whole_path(): void
    {
        $path = $this->paths->generate('A.Bayu@Arenaloka.COM', new DateTimeImmutable('2025-12-26 02:43:37'));

        $this->assertSame('arenaloka.com/a/_/b/a.bayu-2025.12.26.02.43.37/', $path);
    }

    public function test_it_rejects_addresses_without_a_domain(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->paths->generate('not-an-email');
    }
}
