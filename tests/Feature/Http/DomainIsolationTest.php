<?php

namespace Tests\Feature\Http;

use App\Models\Vmail\Alias;
use App\Models\Vmail\Domain;
use App\Models\Vmail\DomainAdmin;
use App\Models\Vmail\Mailbox;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

/**
 * A domain administrator must never see or touch another domain's accounts.
 */
class DomainIsolationTest extends TestCase
{
    use InteractsWithVmail;

    private Mailbox $admin;

    private Mailbox $ownMailbox;

    private Mailbox $otherMailbox;

    protected function setUp(): void
    {
        parent::setUp();

        Domain::factory()->create(['domain' => 'mine.test']);
        Domain::factory()->create(['domain' => 'theirs.test']);

        $this->admin = Mailbox::factory()->inDomain('mine.test')->create();
        DomainAdmin::query()->create(['username' => $this->admin->username, 'domain' => 'mine.test']);

        $this->ownMailbox = Mailbox::factory()->inDomain('mine.test')->create();
        $this->otherMailbox = Mailbox::factory()->inDomain('theirs.test')->create();
    }

    public function test_the_mailbox_list_hides_other_domains(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.index')
            ->assertSee($this->ownMailbox->username)
            ->assertDontSee($this->otherMailbox->username);
    }

    public function test_the_domain_list_hides_other_domains(): void
    {
        Livewire::actingAs($this->admin)
            ->test('domains.index')
            ->assertSee('mine.test')
            ->assertDontSee('theirs.test');
    }

    public function test_editing_a_mailbox_in_another_domain_is_forbidden(): void
    {
        $this->actingAs($this->admin)
            ->get(route('mailboxes.edit', $this->otherMailbox->username))
            ->assertForbidden();
    }

    public function test_editing_a_domain_they_do_not_administer_is_forbidden(): void
    {
        $this->actingAs($this->admin)
            ->get(route('domains.edit', 'theirs.test'))
            ->assertForbidden();
    }

    public function test_only_a_global_admin_may_create_a_domain(): void
    {
        $this->actingAs($this->admin)->get(route('domains.create'))->assertForbidden();

        $this->actingAs(Mailbox::factory()->globalAdmin()->create())
            ->get(route('domains.create'))
            ->assertOk();
    }

    public function test_the_new_mailbox_form_only_offers_their_own_domains(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.form')
            ->assertSee('@mine.test')
            ->assertDontSee('@theirs.test');
    }

    public function test_a_mailbox_cannot_be_created_in_another_domain(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.form')
            ->set('localPart', 'intruder')
            ->set('domain', 'theirs.test')
            ->set('password', 'a-long-enough-password')
            ->set('passwordConfirmation', 'a-long-enough-password')
            ->call('save')
            ->assertHasErrors('domain');

        $this->assertNull(Mailbox::query()->find('intruder@theirs.test'));
    }

    public function test_deleting_a_mailbox_in_another_domain_is_forbidden(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.index')
            ->set('deleting', $this->otherMailbox->username)
            ->set('deleteConfirmation', $this->otherMailbox->username)
            ->call('delete')
            ->assertForbidden();

        $this->assertNotNull(Mailbox::query()->find($this->otherMailbox->username));
    }

    public function test_the_alias_list_hides_other_domains(): void
    {
        $mine = Alias::factory()->inDomain('mine.test')->create(['address' => 'sales@mine.test']);
        $theirs = Alias::factory()->inDomain('theirs.test')->create(['address' => 'sales@theirs.test']);

        Livewire::actingAs($this->admin)
            ->test('aliases.index')
            ->assertSee($mine->address)
            ->assertDontSee($theirs->address);
    }

    public function test_editing_an_alias_in_another_domain_is_forbidden(): void
    {
        $theirs = Alias::factory()->inDomain('theirs.test')->create(['address' => 'sales@theirs.test']);

        $this->actingAs($this->admin)
            ->get(route('aliases.edit', $theirs->address))
            ->assertForbidden();
    }

    public function test_deleting_an_alias_in_another_domain_is_forbidden(): void
    {
        $theirs = Alias::factory()->inDomain('theirs.test')->create(['address' => 'sales@theirs.test']);

        Livewire::actingAs($this->admin)
            ->test('aliases.index')
            ->set('deleting', $theirs->address)
            ->call('delete')
            ->assertForbidden();

        $this->assertNotNull(Alias::query()->find($theirs->address));
    }

    public function test_an_alias_cannot_be_created_in_another_domain(): void
    {
        Livewire::actingAs($this->admin)
            ->test('aliases.form')
            ->set('localPart', 'intruder')
            ->set('domain', 'theirs.test')
            ->call('save')
            ->assertHasErrors('domain');

        $this->assertNull(Alias::query()->find('intruder@theirs.test'));
    }

    public function test_deactivating_a_domain_they_do_not_administer_is_forbidden(): void
    {
        Livewire::actingAs($this->admin)
            ->test('domains.index')
            ->call('toggleActive', 'theirs.test')
            ->assertForbidden();

        $this->assertTrue(Domain::query()->find('theirs.test')->active);
    }
}
