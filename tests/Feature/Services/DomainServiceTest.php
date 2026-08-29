<?php

namespace Tests\Feature\Services;

use App\Models\Vmail\DeletedMailbox;
use App\Models\Vmail\Domain;
use App\Models\Vmail\Forwarding;
use App\Models\Vmail\Mailbox;
use App\Services\AliasService;
use App\Services\DomainService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class DomainServiceTest extends TestCase
{
    use InteractsWithVmail;

    private DomainService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DomainService::class);
    }

    public function test_it_creates_a_domain_with_iredmail_defaults(): void
    {
        $domain = $this->service->create(['domain' => 'Example.Test', 'description' => 'Acme']);

        $this->assertSame('example.test', $domain->domain);
        $this->assertSame('dovecot', $domain->transport);
        $this->assertTrue($domain->active);
        $this->assertSame('9999-12-31', $domain->expired->toDateString());
    }

    public function test_it_refuses_a_duplicate_domain(): void
    {
        $this->service->create(['domain' => 'example.test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->create(['domain' => 'example.test']);
    }

    public function test_it_refuses_a_name_already_used_as_an_alias_domain(): void
    {
        DB::connection('vmail')->table('alias_domain')
            ->insert(['alias_domain' => 'taken.test', 'target_domain' => 'other.test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already used as an alias domain');

        $this->service->create(['domain' => 'taken.test']);
    }

    public function test_a_domain_cannot_be_renamed(): void
    {
        $domain = $this->service->create(['domain' => 'example.test']);

        $this->service->update($domain, ['domain' => 'hijack.test', 'description' => 'Updated']);

        $this->assertSame('example.test', $domain->fresh()->domain);
        $this->assertSame('Updated', $domain->fresh()->description);
    }

    public function test_deleting_a_domain_queues_every_mailbox_for_cleanup(): void
    {
        $domain = $this->service->create(['domain' => 'example.test']);
        Mailbox::factory()->count(3)->inDomain('example.test')->create();

        $this->service->delete($domain, 'admin@example.test');

        $this->assertNull(Domain::query()->find('example.test'));
        $this->assertSame(0, Mailbox::query()->where('domain', 'example.test')->count());
        $this->assertSame(3, DeletedMailbox::query()->where('domain', 'example.test')->count());
    }

    public function test_deleting_a_domain_removes_its_aliases_and_admin_grants(): void
    {
        $domain = $this->service->create(['domain' => 'example.test']);
        $vmail = DB::connection('vmail');

        $vmail->table('alias')->insert(['address' => 'info@example.test', 'domain' => 'example.test']);
        $vmail->table('domain_admins')->insert(['username' => 'admin@other.test', 'domain' => 'example.test']);
        $vmail->table('alias_domain')->insert(['alias_domain' => 'mirror.test', 'target_domain' => 'example.test']);

        $this->service->delete($domain, 'admin@example.test');

        $this->assertSame(0, $vmail->table('alias')->where('domain', 'example.test')->count());
        $this->assertSame(0, $vmail->table('domain_admins')->where('domain', 'example.test')->count());
        $this->assertSame(0, $vmail->table('alias_domain')->where('target_domain', 'example.test')->count());
    }

    public function test_deleting_a_domain_removes_the_recipient_rows_of_its_aliases(): void
    {
        $domain = $this->service->create(['domain' => 'example.test']);
        $alias = app(AliasService::class)->create(
            ['address' => 'sales@example.test'],
            ['a@other.test', 'b@other.test'],
        );

        $this->service->delete($domain, 'admin@example.test');

        $this->assertSame(0, Forwarding::query()->where('address', $alias->address)->count());
        $this->assertSame(0, Forwarding::query()->where('domain', 'example.test')->count());
    }

    /**
     * Another domain's alias may list an address in the deleted domain. Those
     * rows belong to that other domain, so they are left for its admin to fix
     * rather than silently rewritten here.
     */
    public function test_deleting_a_domain_leaves_another_domains_alias_rows_alone(): void
    {
        $domain = $this->service->create(['domain' => 'example.test']);
        $this->service->create(['domain' => 'other.test']);

        app(AliasService::class)->create(['address' => 'team@other.test'], ['someone@example.test']);

        $this->service->delete($domain, 'admin@example.test');

        $this->assertSame(1, Forwarding::query()->where('address', 'team@other.test')->count());
    }

    public function test_deactivating_a_domain_leaves_its_mailboxes_in_place(): void
    {
        $domain = $this->service->create(['domain' => 'example.test']);
        Mailbox::factory()->inDomain('example.test')->create();

        $this->service->setActive($domain, false);

        $this->assertFalse($domain->fresh()->active);
        $this->assertSame(1, Mailbox::query()->where('domain', 'example.test')->count());
    }
}
