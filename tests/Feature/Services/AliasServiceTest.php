<?php

namespace Tests\Feature\Services;

use App\Models\Vmail\Alias;
use App\Models\Vmail\Domain;
use App\Models\Vmail\Forwarding;
use App\Models\Vmail\Mailbox;
use App\Services\AliasService;
use App\Services\ForwardingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class AliasServiceTest extends TestCase
{
    use InteractsWithVmail;

    private AliasService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AliasService::class);
        Domain::factory()->create(['domain' => 'example.test']);
    }

    public function test_it_creates_an_alias_with_its_recipients(): void
    {
        $alias = $this->service->create(
            ['address' => 'Sales@Example.Test', 'name' => 'Sales team'],
            ['a@example.test', 'b@other.test'],
        );

        $this->assertSame('sales@example.test', $alias->address);
        $this->assertSame('example.test', $alias->domain);
        $this->assertTrue($alias->active);

        $members = $alias->members()->orderBy('forwarding')->get();
        $this->assertSame(['a@example.test', 'b@other.test'], $members->pluck('forwarding')->all());
    }

    /**
     * Postfix needs a row per recipient; the `alias` row on its own only makes
     * the address accepted, then the mail is dropped.
     */
    public function test_recipient_rows_carry_the_alias_flag_and_both_domains(): void
    {
        $alias = $this->service->create(['address' => 'sales@example.test'], ['someone@other.test']);

        $member = $alias->members()->sole();

        $this->assertTrue($member->is_alias);
        $this->assertFalse($member->is_forwarding);
        $this->assertSame('example.test', $member->domain);
        $this->assertSame('other.test', $member->dest_domain);
        $this->assertTrue($member->active);
    }

    public function test_it_lowercases_and_deduplicates_recipients(): void
    {
        $alias = $this->service->create(
            ['address' => 'sales@example.test'],
            ['  Someone@Other.Test ', 'someone@other.test', ''],
        );

        $this->assertSame(['someone@other.test'], $alias->members()->pluck('forwarding')->all());
    }

    /**
     * An alias listing itself would bounce mail back through Postfix forever.
     */
    public function test_it_drops_a_recipient_that_is_the_alias_itself(): void
    {
        $alias = $this->service->create(
            ['address' => 'sales@example.test'],
            ['sales@example.test', 'real@other.test'],
        );

        $this->assertSame(['real@other.test'], $alias->members()->pluck('forwarding')->all());
    }

    public function test_it_refuses_an_address_already_used_by_a_mailbox(): void
    {
        Mailbox::factory()->create(['username' => 'taken@example.test', 'domain' => 'example.test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists as a mailbox');

        $this->service->create(['address' => 'taken@example.test']);
    }

    public function test_it_refuses_an_address_already_used_by_another_alias(): void
    {
        $this->service->create(['address' => 'sales@example.test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists as a alias');

        $this->service->create(['address' => 'sales@example.test']);
    }

    public function test_it_refuses_an_address_already_used_by_a_mailing_list(): void
    {
        DB::connection('vmail')->table('maillists')->insert([
            'address' => 'announce@example.test', 'domain' => 'example.test', 'mlid' => 'test-mlid',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists as a maillist');

        $this->service->create(['address' => 'announce@example.test']);
    }

    public function test_it_refuses_an_unknown_domain(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->service->create(['address' => 'sales@nowhere.test']);
    }

    public function test_it_enforces_the_domain_alias_limit(): void
    {
        Domain::factory()->create(['domain' => 'limited.test', 'aliases' => 1]);
        $this->service->create(['address' => 'first@limited.test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('maximum of 1 aliases');

        $this->service->create(['address' => 'second@limited.test']);
    }

    public function test_syncing_adds_and_removes_recipients_in_one_step(): void
    {
        $alias = $this->service->create(
            ['address' => 'sales@example.test'],
            ['keep@other.test', 'drop@other.test'],
        );

        $this->service->syncMembers($alias, ['keep@other.test', 'add@other.test']);

        $this->assertSame(
            ['add@other.test', 'keep@other.test'],
            $alias->members()->orderBy('forwarding')->pluck('forwarding')->all(),
        );
    }

    public function test_syncing_an_empty_list_removes_every_recipient(): void
    {
        $alias = $this->service->create(['address' => 'sales@example.test'], ['a@other.test']);

        $this->service->syncMembers($alias, []);

        $this->assertSame(0, $alias->members()->count());
    }

    public function test_updating_cannot_change_the_address(): void
    {
        $alias = $this->service->create(['address' => 'sales@example.test'], ['a@other.test']);

        $this->service->update($alias, ['address' => 'hijack@example.test', 'name' => 'Renamed']);

        $this->assertNotNull(Alias::query()->find('sales@example.test'));
        $this->assertNull(Alias::query()->find('hijack@example.test'));
        $this->assertSame('Renamed', $alias->fresh()->name);
    }

    public function test_updating_without_a_member_list_leaves_recipients_alone(): void
    {
        $alias = $this->service->create(['address' => 'sales@example.test'], ['a@other.test']);

        $this->service->update($alias, ['name' => 'Renamed']);

        $this->assertSame(['a@other.test'], $alias->members()->pluck('forwarding')->all());
    }

    public function test_deleting_an_alias_removes_its_recipients(): void
    {
        $alias = $this->service->create(['address' => 'sales@example.test'], ['a@other.test', 'b@other.test']);

        $this->service->delete($alias);

        $this->assertNull(Alias::query()->find('sales@example.test'));
        $this->assertSame(0, Forwarding::query()->where('address', 'sales@example.test')->count());
    }

    /**
     * A mailbox's own forwarding rows share the `forwardings` table; syncing an
     * alias must not touch them.
     */
    public function test_syncing_an_alias_leaves_user_forwarding_rows_alone(): void
    {
        $mailbox = Mailbox::factory()->inDomain('example.test')->create();
        app(ForwardingService::class)->sync($mailbox, ['elsewhere@other.test']);

        $alias = $this->service->create(['address' => 'sales@example.test'], ['a@other.test']);
        $this->service->syncMembers($alias, []);

        $this->assertSame(2, Forwarding::query()->where('address', $mailbox->username)->count());
    }
}
