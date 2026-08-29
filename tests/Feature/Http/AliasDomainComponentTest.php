<?php

namespace Tests\Feature\Http;

use App\Models\Vmail\AliasDomain;
use App\Models\Vmail\Domain;
use App\Models\Vmail\DomainAdmin;
use App\Models\Vmail\Mailbox;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class AliasDomainComponentTest extends TestCase
{
    use InteractsWithVmail;

    private Mailbox $admin;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domain = Domain::factory()->create(['domain' => 'example.test']);
        $this->admin = Mailbox::factory()->globalAdmin()->create();
    }

    public function test_the_domain_edit_page_shows_the_alias_domain_section(): void
    {
        $this->actingAs($this->admin)
            ->get(route('domains.edit', $this->domain->domain))
            ->assertOk()
            ->assertSee('Domain alias')
            // Blade treats `@{{` as an escape, so the target address has to be
            // interpolated as one expression rather than written inline.
            ->assertSee('nama@example.test')
            ->assertDontSee('{{');
    }

    public function test_it_adds_an_alias_domain(): void
    {
        Livewire::actingAs($this->admin)
            ->test('domains.alias-domains', ['domain' => $this->domain])
            ->set('newAliasDomain', 'Mirror.Test')
            ->call('add')
            ->assertHasNoErrors()
            ->assertSet('newAliasDomain', '');

        $this->assertSame('example.test', AliasDomain::query()->findOrFail('mirror.test')->target_domain);
    }

    public function test_it_rejects_a_name_that_is_not_a_domain(): void
    {
        Livewire::actingAs($this->admin)
            ->test('domains.alias-domains', ['domain' => $this->domain])
            ->set('newAliasDomain', 'not a domain')
            ->call('add')
            ->assertHasErrors('newAliasDomain');
    }

    public function test_it_reports_a_name_already_taken_by_a_mail_domain(): void
    {
        Domain::factory()->create(['domain' => 'taken.test']);

        Livewire::actingAs($this->admin)
            ->test('domains.alias-domains', ['domain' => $this->domain])
            ->set('newAliasDomain', 'taken.test')
            ->call('add')
            ->assertHasErrors('newAliasDomain');

        $this->assertNull(AliasDomain::query()->find('taken.test'));
    }

    public function test_it_removes_and_deactivates_an_alias_domain(): void
    {
        AliasDomain::factory()->targeting('example.test')->create(['alias_domain' => 'mirror.test']);

        $component = Livewire::actingAs($this->admin)
            ->test('domains.alias-domains', ['domain' => $this->domain]);

        $component->call('toggleActive', 'mirror.test');
        $this->assertFalse(AliasDomain::query()->findOrFail('mirror.test')->active);

        $component->call('remove', 'mirror.test');
        $this->assertNull(AliasDomain::query()->find('mirror.test'));
    }

    /**
     * The row is looked up within the domain being edited, so a forged
     * parameter cannot reach another domain's alias domain.
     */
    public function test_it_refuses_to_touch_an_alias_domain_of_another_domain(): void
    {
        Domain::factory()->create(['domain' => 'theirs.test']);
        AliasDomain::factory()->targeting('theirs.test')->create(['alias_domain' => 'notmine.test']);

        try {
            Livewire::actingAs($this->admin)
                ->test('domains.alias-domains', ['domain' => $this->domain])
                ->call('remove', 'notmine.test');

            $this->fail('Expected the lookup to fail for an alias domain of another domain.');
        } catch (ModelNotFoundException) {
            // The scoped lookup refused it, which is the point.
        }

        $this->assertNotNull(AliasDomain::query()->find('notmine.test'));
    }

    public function test_a_domain_admin_cannot_open_the_section_for_another_domain(): void
    {
        $other = Domain::factory()->create(['domain' => 'theirs.test']);

        $admin = Mailbox::factory()->inDomain('example.test')->create();
        DomainAdmin::query()->create(['username' => $admin->username, 'domain' => 'example.test']);

        Livewire::actingAs($admin)
            ->test('domains.alias-domains', ['domain' => $other])
            ->assertForbidden();
    }
}
