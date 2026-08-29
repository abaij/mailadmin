<?php

namespace Tests\Feature\Http;

use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Support\IRedMail\PasswordHasher;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class MailboxFormTest extends TestCase
{
    use InteractsWithVmail;

    private Mailbox $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Domain::factory()->create(['domain' => 'example.test']);
        $this->admin = Mailbox::factory()->globalAdmin()->create();
    }

    /**
     * The edit route previously 404'd because Livewire resolved the route
     * parameter into a model that `mount()` then treated as a string.
     */
    public function test_the_edit_page_loads_for_an_existing_address(): void
    {
        $mailbox = Mailbox::factory()->inDomain('example.test')->create(['name' => 'Existing User']);

        $this->actingAs($this->admin)
            ->get(route('mailboxes.edit', $mailbox->username))
            ->assertOk()
            ->assertSee($mailbox->username)
            ->assertSee('Existing User');
    }

    public function test_the_edit_page_404s_for_an_unknown_address(): void
    {
        $this->actingAs($this->admin)
            ->get(route('mailboxes.edit', 'ghost@example.test'))
            ->assertNotFound();
    }

    public function test_it_creates_a_mailbox(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.form')
            ->set('localPart', 'new.user')
            ->set('domain', 'example.test')
            ->set('name', 'New User')
            ->set('quota', 2048)
            ->set('password', 'a-long-enough-password')
            ->set('passwordConfirmation', 'a-long-enough-password')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('mailboxes.index'));

        $created = Mailbox::query()->findOrFail('new.user@example.test');

        $this->assertSame('New User', $created->name);
        $this->assertSame(2048, $created->quota);
        $this->assertTrue(app(PasswordHasher::class)->verify('a-long-enough-password', $created->password));
    }

    public function test_it_rejects_a_password_that_does_not_match_the_confirmation(): void
    {
        Livewire::actingAs($this->admin)
            ->test('mailboxes.form')
            ->set('localPart', 'mismatch')
            ->set('domain', 'example.test')
            ->set('password', 'a-long-enough-password')
            ->set('passwordConfirmation', 'something-else-entirely')
            ->call('save')
            ->assertHasErrors('password');

        $this->assertNull(Mailbox::query()->find('mismatch@example.test'));
    }

    public function test_it_rejects_a_local_part_the_maildir_hash_cannot_represent(): void
    {
        foreach (['Has Space', 'UPPERCASE', '.leading', 'trailing.', 'sla/sh', 'kutip\'an'] as $localPart) {
            Livewire::actingAs($this->admin)
                ->test('mailboxes.form')
                ->set('localPart', $localPart)
                ->set('domain', 'example.test')
                ->set('password', 'a-long-enough-password')
                ->set('passwordConfirmation', 'a-long-enough-password')
                ->call('save')
                ->assertHasErrors('localPart');
        }
    }

    public function test_editing_leaves_the_password_alone_when_the_field_is_blank(): void
    {
        $mailbox = Mailbox::factory()->inDomain('example.test')->create();
        $original = $mailbox->password;

        Livewire::actingAs($this->admin)
            ->test('mailboxes.form', ['mailbox' => $mailbox])
            ->set('name', 'Renamed')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $mailbox->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertSame($original, $fresh->password);
    }

    public function test_editing_changes_the_password_when_one_is_supplied(): void
    {
        $mailbox = Mailbox::factory()->inDomain('example.test')->create();

        Livewire::actingAs($this->admin)
            ->test('mailboxes.form', ['mailbox' => $mailbox])
            ->set('password', 'a-brand-new-password')
            ->set('passwordConfirmation', 'a-brand-new-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(app(PasswordHasher::class)->verify('a-brand-new-password', $mailbox->fresh()->password));
    }

    public function test_disabling_a_service_group_clears_every_column_behind_it(): void
    {
        $mailbox = Mailbox::factory()->inDomain('example.test')->create();

        Livewire::actingAs($this->admin)
            ->test('mailboxes.form', ['mailbox' => $mailbox])
            ->set('services.imap', false)
            ->set('services.sogo', false)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $mailbox->fresh();

        foreach (Mailbox::SERVICE_GROUPS['imap'] as $column) {
            $this->assertSame(0, (int) $fresh->{$column}, "{$column} should be off");
        }

        // The SOGo feature columns are char(1) 'y'/'n' rather than tinyint.
        $this->assertSame('n', $fresh->enablesogowebmail);
        $this->assertSame(0, (int) $fresh->enablesogo);

        // Untouched groups stay on.
        $this->assertSame(1, (int) $fresh->enablesmtp);
    }
}
