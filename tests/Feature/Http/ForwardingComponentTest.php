<?php

namespace Tests\Feature\Http;

use App\Models\Vmail\Domain;
use App\Models\Vmail\DomainAdmin;
use App\Models\Vmail\Mailbox;
use App\Services\ForwardingService;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class ForwardingComponentTest extends TestCase
{
    use InteractsWithVmail;

    private Mailbox $admin;

    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        Domain::factory()->create(['domain' => 'example.test']);
        $this->admin = Mailbox::factory()->globalAdmin()->create();
        $this->mailbox = Mailbox::factory()->inDomain('example.test')->create();
    }

    public function test_the_mailbox_edit_page_shows_the_forwarding_section(): void
    {
        $this->actingAs($this->admin)
            ->get(route('mailboxes.edit', $this->mailbox->username))
            ->assertOk()
            ->assertSee('Penerusan email');
    }

    public function test_it_saves_destinations_and_keeps_a_copy_by_default(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.forwarding', ['mailbox' => $this->mailbox])
            ->set('destinations', "a@other.test\nB@Other.Test")
            ->call('save')
            ->assertHasNoErrors();

        $service = app(ForwardingService::class);

        $this->assertSame(['a@other.test', 'b@other.test'], $service->destinationsFor($this->mailbox));
        $this->assertTrue($service->keepsCopy($this->mailbox));
    }

    public function test_it_warns_when_forwarding_would_bypass_the_mailbox(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.forwarding', ['mailbox' => $this->mailbox])
            ->set('destinations', 'a@other.test')
            ->set('keepCopy', false)
            ->assertSee('tidak</strong> masuk ke kotak surat ini', escape: false);
    }

    public function test_it_does_not_warn_while_a_copy_is_kept(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.forwarding', ['mailbox' => $this->mailbox])
            ->set('destinations', 'a@other.test')
            ->set('keepCopy', true)
            ->assertDontSee('tidak</strong> masuk ke kotak surat ini', escape: false);
    }

    public function test_it_rejects_a_destination_that_is_not_an_email_address(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.forwarding', ['mailbox' => $this->mailbox])
            ->set('destinations', 'nonsense')
            ->call('save')
            ->assertHasErrors('destinations');
    }

    public function test_clearing_the_field_turns_forwarding_off(): void
    {
        app(ForwardingService::class)->sync($this->mailbox, ['a@other.test'], keepCopy: true);

        Livewire::actingAs($this->admin)
            ->test('mailboxes.forwarding', ['mailbox' => $this->mailbox])
            ->set('destinations', '')
            ->set('keepCopy', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([], app(ForwardingService::class)->destinationsFor($this->mailbox));
        $this->assertFalse(app(ForwardingService::class)->keepsCopy($this->mailbox));
    }

    /**
     * A mailbox carrying only the keep-a-copy row -- the shape found in the
     * production data -- must load with the box ticked and no destinations.
     */
    public function test_it_loads_a_mailbox_that_only_keeps_a_copy(): void
    {
        app(ForwardingService::class)->sync($this->mailbox, [], keepCopy: true);

        Livewire::actingAs($this->admin)
            ->test('mailboxes.forwarding', ['mailbox' => $this->mailbox])
            ->assertSet('destinations', '')
            ->assertSet('keepCopy', true);
    }

    public function test_a_domain_admin_cannot_reach_another_domains_forwarding(): void
    {
        Domain::factory()->create(['domain' => 'theirs.test']);
        $other = Mailbox::factory()->inDomain('theirs.test')->create();

        $admin = Mailbox::factory()->inDomain('example.test')->create();
        DomainAdmin::query()->create([
            'username' => $admin->username, 'domain' => 'example.test',
        ]);

        Livewire::actingAs($admin)
            ->test('mailboxes.forwarding', ['mailbox' => $other])
            ->assertForbidden();
    }
}
