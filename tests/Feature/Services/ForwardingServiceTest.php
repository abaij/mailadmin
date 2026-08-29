<?php

namespace Tests\Feature\Services;

use App\Models\Vmail\Domain;
use App\Models\Vmail\Forwarding;
use App\Models\Vmail\Mailbox;
use App\Services\ForwardingService;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class ForwardingServiceTest extends TestCase
{
    use InteractsWithVmail;

    private ForwardingService $service;

    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ForwardingService::class);
        Domain::factory()->create(['domain' => 'example.test']);
        $this->mailbox = Mailbox::factory()->inDomain('example.test')->create();
    }

    /**
     * The row pointing at the mailbox itself is what keeps a local copy; the
     * production database contains mailboxes that carry only this row.
     */
    public function test_keeping_a_copy_writes_a_row_pointing_at_the_mailbox_itself(): void
    {
        $this->service->sync($this->mailbox, ['elsewhere@other.test'], keepCopy: true);

        $rows = Forwarding::query()->where('address', $this->mailbox->username)
            ->orderBy('forwarding')->pluck('forwarding')->all();

        $this->assertContains($this->mailbox->username, $rows);
        $this->assertContains('elsewhere@other.test', $rows);
        $this->assertTrue($this->service->keepsCopy($this->mailbox));
    }

    public function test_forwarding_without_keeping_a_copy_writes_no_self_row(): void
    {
        $this->service->sync($this->mailbox, ['elsewhere@other.test'], keepCopy: false);

        $this->assertSame(
            ['elsewhere@other.test'],
            Forwarding::query()->where('address', $this->mailbox->username)->pluck('forwarding')->all(),
        );
        $this->assertFalse($this->service->keepsCopy($this->mailbox));
        $this->assertTrue($this->service->forwardsWithoutKeepingACopy($this->mailbox));
    }

    public function test_keeping_a_copy_with_no_destinations_still_writes_the_self_row(): void
    {
        $this->service->sync($this->mailbox, [], keepCopy: true);

        $row = Forwarding::query()->where('address', $this->mailbox->username)->sole();

        $this->assertTrue($row->isSelfCopy());
        $this->assertTrue($row->is_forwarding);
        $this->assertSame([], $this->service->destinationsFor($this->mailbox));
        $this->assertFalse($this->service->forwardsWithoutKeepingACopy($this->mailbox));
    }

    public function test_clearing_everything_removes_all_rows(): void
    {
        $this->service->sync($this->mailbox, ['a@other.test'], keepCopy: true);

        $this->service->sync($this->mailbox, [], keepCopy: false);

        $this->assertSame(0, Forwarding::query()->where('address', $this->mailbox->username)->count());
    }

    public function test_rows_carry_the_forwarding_flag_and_both_domains(): void
    {
        $this->service->sync($this->mailbox, ['someone@other.test'], keepCopy: false);

        $row = Forwarding::query()->where('address', $this->mailbox->username)->sole();

        $this->assertTrue($row->is_forwarding);
        $this->assertFalse($row->is_alias);
        $this->assertSame('example.test', $row->domain);
        $this->assertSame('other.test', $row->dest_domain);
    }

    public function test_it_lowercases_deduplicates_and_drops_blanks(): void
    {
        $this->service->sync($this->mailbox, [' A@Other.Test ', 'a@other.test', ''], keepCopy: false);

        $this->assertSame(['a@other.test'], $this->service->destinationsFor($this->mailbox));
    }

    /**
     * Typing your own address as a destination is the keep-a-copy toggle, not
     * a second destination.
     */
    public function test_the_mailbox_own_address_is_never_treated_as_a_destination(): void
    {
        $this->service->sync($this->mailbox, [$this->mailbox->username, 'a@other.test'], keepCopy: false);

        $this->assertSame(['a@other.test'], $this->service->destinationsFor($this->mailbox));
        $this->assertFalse($this->service->keepsCopy($this->mailbox));
    }

    public function test_syncing_replaces_the_previous_destinations(): void
    {
        $this->service->sync($this->mailbox, ['old@other.test', 'keep@other.test'], keepCopy: true);

        $this->service->sync($this->mailbox, ['keep@other.test', 'new@other.test'], keepCopy: true);

        $this->assertSame(['keep@other.test', 'new@other.test'], $this->service->destinationsFor($this->mailbox));
        $this->assertTrue($this->service->keepsCopy($this->mailbox));
    }

    public function test_turning_off_keep_a_copy_removes_only_the_self_row(): void
    {
        $this->service->sync($this->mailbox, ['a@other.test'], keepCopy: true);

        $this->service->sync($this->mailbox, ['a@other.test'], keepCopy: false);

        $this->assertSame(['a@other.test'], $this->service->destinationsFor($this->mailbox));
        $this->assertFalse($this->service->keepsCopy($this->mailbox));
    }

    /**
     * Alias recipients live in the same table; forwarding must not disturb them.
     */
    public function test_it_leaves_alias_recipient_rows_alone(): void
    {
        Forwarding::query()->create([
            'address' => $this->mailbox->username, 'forwarding' => 'member@other.test',
            'domain' => 'example.test', 'dest_domain' => 'other.test', 'is_alias' => 1, 'active' => 1,
        ]);

        $this->service->sync($this->mailbox, [], keepCopy: false);

        $this->assertSame(1, Forwarding::query()->aliasMembers()
            ->where('address', $this->mailbox->username)->count());
    }
}
