<?php

namespace Tests\Feature\Http;

use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class DomainFormTest extends TestCase
{
    use InteractsWithVmail;

    private Mailbox $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Mailbox::factory()->globalAdmin()->create();
    }

    public function test_it_creates_a_domain_with_all_three_limits(): void
    {
        Livewire::actingAs($this->admin)
            ->test('domains.form')
            ->set('name', 'example.test')
            ->set('maxquota', 5000)
            ->set('mailboxLimit', 20)
            ->set('aliasLimit', 10)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('domains.index'));

        $domain = Domain::query()->findOrFail('example.test');

        $this->assertSame(5000, $domain->maxquota);
        $this->assertSame(20, $domain->mailboxes);
        $this->assertSame(10, $domain->aliases);
    }

    /**
     * The alias limit is enforced when creating aliases, so an admin must be
     * able to see and change it.
     */
    public function test_the_edit_form_loads_the_existing_alias_limit(): void
    {
        $domain = Domain::factory()->create(['domain' => 'example.test', 'aliases' => 7]);

        Livewire::actingAs($this->admin)
            ->test('domains.form', ['domain' => $domain])
            ->assertSet('aliasLimit', 7)
            ->set('aliasLimit', 12)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(12, $domain->fresh()->aliases);
    }

    public function test_it_rejects_a_negative_limit(): void
    {
        Livewire::actingAs($this->admin)
            ->test('domains.form')
            ->set('name', 'example.test')
            ->set('aliasLimit', -1)
            ->call('save')
            ->assertHasErrors('aliasLimit');
    }
}
