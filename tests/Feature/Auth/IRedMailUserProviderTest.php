<?php

namespace Tests\Feature\Auth;

use App\Models\Vmail\Mailbox;
use Database\Factories\Vmail\MailboxFactory;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class IRedMailUserProviderTest extends TestCase
{
    use InteractsWithVmail;

    public function test_it_signs_in_an_active_mailbox_with_its_iredmail_password(): void
    {
        $mailbox = Mailbox::factory()->create();

        $this->assertTrue(Auth::attempt([
            'username' => $mailbox->username,
            'password' => MailboxFactory::PASSWORD,
        ]));

        $this->assertSame($mailbox->username, Auth::user()->username);
    }

    public function test_it_rejects_a_wrong_password(): void
    {
        $mailbox = Mailbox::factory()->create();

        $this->assertFalse(Auth::attempt([
            'username' => $mailbox->username,
            'password' => 'wrong',
        ]));
    }

    public function test_it_matches_the_username_case_insensitively(): void
    {
        $mailbox = Mailbox::factory()->create(['username' => 'casing@example.test', 'domain' => 'example.test']);

        $this->assertTrue(Auth::attempt([
            'username' => '  Casing@Example.Test  ',
            'password' => MailboxFactory::PASSWORD,
        ]));
    }

    public function test_it_refuses_deactivated_accounts(): void
    {
        $mailbox = Mailbox::factory()->inactive()->create();

        $this->assertFalse(Auth::attempt([
            'username' => $mailbox->username,
            'password' => MailboxFactory::PASSWORD,
        ]));
    }

    public function test_it_refuses_expired_accounts(): void
    {
        $mailbox = Mailbox::factory()->expired()->create();

        $this->assertFalse(Auth::attempt([
            'username' => $mailbox->username,
            'password' => MailboxFactory::PASSWORD,
        ]));
    }

    public function test_it_rejects_an_empty_password_against_an_empty_hash(): void
    {
        $mailbox = Mailbox::factory()->create(['password' => '']);

        $this->assertFalse(Auth::attempt([
            'username' => $mailbox->username,
            'password' => '',
        ]));
    }

    /**
     * A login must never write to the mail server's database.
     */
    public function test_it_does_not_rehash_the_stored_password_on_login(): void
    {
        $mailbox = Mailbox::factory()->create();
        $original = $mailbox->password;

        Auth::attempt(['username' => $mailbox->username, 'password' => MailboxFactory::PASSWORD]);

        $this->assertSame($original, $mailbox->fresh()->password);
    }
}
