<?php

namespace Tests\Feature\Services;

use App\Models\Vmail\AliasDomain;
use App\Models\Vmail\Domain;
use App\Services\AliasDomainService;
use App\Services\DomainService;
use RuntimeException;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class AliasDomainServiceTest extends TestCase
{
    use InteractsWithVmail;

    private AliasDomainService $service;

    private Domain $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AliasDomainService::class);
        $this->target = Domain::factory()->create(['domain' => 'example.test']);
    }

    public function test_it_points_an_alias_domain_at_a_target(): void
    {
        $alias = $this->service->create($this->target, 'Mirror.Test');

        $this->assertSame('mirror.test', $alias->alias_domain);
        $this->assertSame('example.test', $alias->target_domain);
        $this->assertTrue($alias->active);
    }

    public function test_it_refuses_a_name_that_is_already_a_mail_domain(): void
    {
        Domain::factory()->create(['domain' => 'taken.test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already a mail domain');

        $this->service->create($this->target, 'taken.test');
    }

    public function test_it_refuses_a_name_that_is_already_an_alias_domain(): void
    {
        $this->service->create($this->target, 'mirror.test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already an alias domain');

        $this->service->create($this->target, 'mirror.test');
    }

    /**
     * The reverse check: a mail domain must not shadow an existing alias domain.
     */
    public function test_a_mail_domain_cannot_reuse_an_alias_domain_name(): void
    {
        $this->service->create($this->target, 'mirror.test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already used as an alias domain');

        app(DomainService::class)->create(['domain' => 'mirror.test']);
    }

    public function test_it_deactivates_and_deletes(): void
    {
        $alias = $this->service->create($this->target, 'mirror.test');

        $this->service->setActive($alias, false);
        $this->assertFalse($alias->fresh()->active);

        $this->service->delete($alias);
        $this->assertNull(AliasDomain::query()->find('mirror.test'));
    }

    public function test_deleting_the_target_domain_removes_its_alias_domains(): void
    {
        $this->service->create($this->target, 'mirror.test');

        app(DomainService::class)->delete($this->target, 'admin@example.test');

        $this->assertNull(AliasDomain::query()->find('mirror.test'));
    }
}
