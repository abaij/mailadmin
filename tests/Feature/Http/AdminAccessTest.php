<?php

namespace Tests\Feature\Http;

use App\Models\Vmail\DomainAdmin;
use App\Models\Vmail\Mailbox;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use InteractsWithVmail;

    public static function adminRoutes(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'domains' => ['/domains'],
            'mailboxes' => ['/mailboxes'],
            'aliases' => ['/aliases'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_sent_to_the_login_page(string $path): void
    {
        $this->get($path)->assertRedirect('/login');
    }

    #[DataProvider('adminRoutes')]
    public function test_a_plain_mail_user_cannot_reach_the_admin_area(string $path): void
    {
        $this->actingAs(Mailbox::factory()->create())->get($path)->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_a_global_admin_can_reach_the_admin_area(string $path): void
    {
        $this->actingAs(Mailbox::factory()->globalAdmin()->create())->get($path)->assertOk();
    }

    #[DataProvider('adminRoutes')]
    public function test_a_domain_admin_can_reach_the_admin_area(string $path): void
    {
        $admin = Mailbox::factory()->inDomain('example.test')->create();
        DomainAdmin::query()->create(['username' => $admin->username, 'domain' => 'example.test']);

        $this->actingAs($admin)->get($path)->assertOk();
    }

    public function test_an_inactive_domain_admin_grant_does_not_grant_access(): void
    {
        $admin = Mailbox::factory()->inDomain('example.test')->create();
        DomainAdmin::query()->create([
            'username' => $admin->username, 'domain' => 'example.test', 'active' => 0,
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
    }
}
