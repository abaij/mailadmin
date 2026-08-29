<?php

namespace Tests\Feature\Http;

use App\Models\Vmail\Alias;
use App\Models\Vmail\Domain;
use App\Models\Vmail\Forwarding;
use App\Models\Vmail\Mailbox;
use App\Services\AliasService;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class AliasFormTest extends TestCase
{
    use InteractsWithVmail;

    private Mailbox $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Domain::factory()->create(['domain' => 'example.test']);
        $this->admin = Mailbox::factory()->globalAdmin()->create();
    }

    public function test_the_edit_page_loads_with_the_current_recipients(): void
    {
        $alias = Alias::factory()->inDomain('example.test')->create(['address' => 'sales@example.test']);
        app(AliasService::class)->syncMembers($alias, ['a@other.test', 'b@other.test']);

        $this->actingAs($this->admin)
            ->get(route('aliases.edit', $alias->address))
            ->assertOk()
            ->assertSee('sales@example.test')
            ->assertSee('a@other.test')
            ->assertSee('b@other.test');
    }

    public function test_the_edit_page_404s_for_an_unknown_alias(): void
    {
        $this->actingAs($this->admin)
            ->get(route('aliases.edit', 'ghost@example.test'))
            ->assertNotFound();
    }

    public function test_it_creates_an_alias_with_recipients_typed_one_per_line(): void
    {
        Livewire::actingAs($this->admin)
            ->test('aliases.form')
            ->set('localPart', 'sales')
            ->set('domain', 'example.test')
            ->set('name', 'Tim penjualan')
            ->set('members', "a@other.test\n  B@Other.Test  \n\nc@other.test")
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('aliases.index'));

        $alias = Alias::query()->findOrFail('sales@example.test');

        $this->assertSame('Tim penjualan', $alias->name);
        $this->assertSame(
            ['a@other.test', 'b@other.test', 'c@other.test'],
            $alias->members()->orderBy('forwarding')->pluck('forwarding')->all(),
        );
    }

    public function test_it_allows_an_alias_with_no_recipients_but_warns_about_it(): void
    {
        Livewire::actingAs($this->admin)
            ->test('aliases.form')
            ->set('localPart', 'empty')
            ->set('domain', 'example.test')
            ->assertSee('membuangnya tanpa jejak')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, Alias::query()->findOrFail('empty@example.test')->members()->count());
    }

    public function test_it_rejects_a_recipient_that_is_not_an_email_address(): void
    {
        Livewire::actingAs($this->admin)
            ->test('aliases.form')
            ->set('localPart', 'sales')
            ->set('domain', 'example.test')
            ->set('members', "ok@other.test\nnot-an-address")
            ->call('save')
            ->assertHasErrors('members');

        $this->assertNull(Alias::query()->find('sales@example.test'));
    }

    public function test_it_rejects_an_address_already_used_by_a_mailbox(): void
    {
        Mailbox::factory()->create(['username' => 'taken@example.test', 'domain' => 'example.test']);

        Livewire::actingAs($this->admin)
            ->test('aliases.form')
            ->set('localPart', 'taken')
            ->set('domain', 'example.test')
            ->call('save')
            ->assertHasErrors('localPart');
    }

    public function test_editing_replaces_the_recipient_list(): void
    {
        $alias = Alias::factory()->inDomain('example.test')->create(['address' => 'sales@example.test']);
        app(AliasService::class)->syncMembers($alias, ['old@other.test']);

        Livewire::actingAs($this->admin)
            ->test('aliases.form', ['alias' => $alias])
            ->set('members', 'new@other.test')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['new@other.test'], $alias->members()->pluck('forwarding')->all());
    }

    public function test_deleting_an_alias_from_the_list_removes_its_recipients(): void
    {
        $alias = Alias::factory()->inDomain('example.test')->create(['address' => 'sales@example.test']);
        app(AliasService::class)->syncMembers($alias, ['a@other.test']);

        Livewire::actingAs($this->admin)
            ->test('aliases.index')
            ->call('confirmDelete', $alias->address)
            ->call('delete');

        $this->assertNull(Alias::query()->find('sales@example.test'));
        $this->assertSame(0, Forwarding::query()->where('address', 'sales@example.test')->count());
    }

    public function test_the_list_flags_an_alias_with_no_recipients(): void
    {
        Alias::factory()->inDomain('example.test')->create(['address' => 'empty@example.test']);

        Livewire::actingAs($this->admin)
            ->test('aliases.index')
            ->assertSee('Tanpa penerima');
    }
}
