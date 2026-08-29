<?php

namespace Tests\Feature\Services;

use App\Models\Vmail\DeletedMailbox;
use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Services\MailboxService;
use App\Support\IRedMail\PasswordHasher;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class MailboxServiceTest extends TestCase
{
    use InteractsWithVmail;

    private MailboxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MailboxService::class);
    }

    public function test_it_creates_a_mailbox_with_a_hashed_maildir_path(): void
    {
        Domain::factory()->create(['domain' => 'example.test']);

        $mailbox = $this->service->create([
            'username' => 'A.Bayu@Example.Test',
            'password' => 'sup3r-s3cret',
            'name' => 'A. Bayu',
            'quota' => 2048,
        ]);

        $this->assertSame('a.bayu@example.test', $mailbox->username);
        $this->assertSame('example.test', $mailbox->domain);
        $this->assertMatchesRegularExpression(
            '#^example\.test/a/_/b/a\.bayu-\d{4}(\.\d{2}){5}/$#',
            $mailbox->maildir,
        );
    }

    public function test_it_stores_a_verifiable_iredmail_password_hash(): void
    {
        Domain::factory()->create(['domain' => 'example.test']);

        $mailbox = $this->service->create([
            'username' => 'user@example.test',
            'password' => 'sup3r-s3cret',
        ]);

        $hasher = app(PasswordHasher::class);

        $this->assertSame(PasswordHasher::SSHA512, $hasher->schemeOf($mailbox->password));
        $this->assertTrue($hasher->verify('sup3r-s3cret', $mailbox->password));
    }

    /**
     * Postfix resolves mailboxes through `virtual_mailbox_maps`, not through
     * `forwardings`, so a plain account must not get an alias row.
     */
    public function test_it_does_not_create_a_forwarding_row_for_a_plain_mailbox(): void
    {
        Domain::factory()->create(['domain' => 'example.test']);

        $mailbox = $this->service->create([
            'username' => 'user@example.test',
            'password' => 'sup3r-s3cret',
        ]);

        $this->assertSame(0, DB::connection('vmail')
            ->table('forwardings')->where('address', $mailbox->username)->count());
    }

    public function test_it_refuses_to_create_a_mailbox_in_an_unknown_domain(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->service->create(['username' => 'user@nowhere.test', 'password' => 'x']);
    }

    public function test_it_refuses_to_create_a_duplicate_address(): void
    {
        Domain::factory()->create(['domain' => 'example.test']);
        $this->service->create(['username' => 'user@example.test', 'password' => 'x']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->create(['username' => 'user@example.test', 'password' => 'y']);
    }

    public function test_it_enforces_the_domain_mailbox_limit(): void
    {
        Domain::factory()->create(['domain' => 'example.test', 'mailboxes' => 1]);
        $this->service->create(['username' => 'first@example.test', 'password' => 'x']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('maximum of 1 mailboxes');

        $this->service->create(['username' => 'second@example.test', 'password' => 'x']);
    }

    public function test_a_mailbox_limit_of_zero_means_unlimited(): void
    {
        Domain::factory()->create(['domain' => 'example.test', 'mailboxes' => 0]);

        $this->service->create(['username' => 'first@example.test', 'password' => 'x']);
        $this->service->create(['username' => 'second@example.test', 'password' => 'x']);

        $this->assertSame(2, Mailbox::query()->where('domain', 'example.test')->count());
    }

    public function test_changing_a_password_keeps_the_existing_hash_scheme(): void
    {
        $hasher = app(PasswordHasher::class);
        $mailbox = Mailbox::factory()->create([
            'password' => $hasher->make('old', PasswordHasher::SHA512_CRYPT),
        ]);

        $this->service->changePassword($mailbox, 'brand-new');

        $this->assertSame(PasswordHasher::SHA512_CRYPT, $hasher->schemeOf($mailbox->password));
        $this->assertTrue($hasher->verify('brand-new', $mailbox->fresh()->password));
    }

    public function test_updating_a_mailbox_cannot_change_its_address_or_maildir(): void
    {
        $mailbox = Mailbox::factory()->create();
        $originalMaildir = $mailbox->maildir;

        $this->service->update($mailbox, [
            'name' => 'Renamed',
            'username' => 'hijack@example.test',
            'maildir' => 'somewhere/else/',
            'password' => 'plaintext',
        ]);

        $fresh = $mailbox->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertSame($originalMaildir, $fresh->maildir);
        $this->assertNotSame('plaintext', $fresh->password);
    }

    public function test_deleting_a_mailbox_queues_its_maildir_for_cleanup(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailbox->usedQuota()->create(['bytes' => 4096, 'messages' => 12]);

        $this->service->delete($mailbox, 'admin@example.test');

        $this->assertNull(Mailbox::query()->find($mailbox->username));

        $queued = DeletedMailbox::query()->where('username', $mailbox->username)->sole();
        $this->assertSame($mailbox->maildir, $queued->maildir);
        $this->assertSame(4096, $queued->bytes);
        $this->assertSame(12, $queued->messages);
        $this->assertSame('admin@example.test', $queued->admin);
    }

    public function test_deleting_a_mailbox_removes_its_related_rows(): void
    {
        $mailbox = Mailbox::factory()->create();
        $vmail = DB::connection('vmail');

        $mailbox->usedQuota()->create(['bytes' => 1, 'messages' => 1]);
        $vmail->table('last_login')->insert(['username' => $mailbox->username, 'domain' => $mailbox->domain, 'imap' => 1]);
        $vmail->table('forwardings')->insert([
            'address' => $mailbox->username, 'forwarding' => 'elsewhere@other.test',
            'domain' => $mailbox->domain, 'dest_domain' => 'other.test', 'is_forwarding' => 1,
        ]);
        $vmail->table('share_folder')->insert(['from_user' => $mailbox->username, 'to_user' => 'peer@other.test']);
        $vmail->table('domain_admins')->insert(['username' => $mailbox->username, 'domain' => $mailbox->domain]);

        $this->service->delete($mailbox, 'admin@example.test');

        foreach (['used_quota' => 'username', 'last_login' => 'username', 'forwardings' => 'address', 'domain_admins' => 'username'] as $table => $column) {
            $this->assertSame(0, $vmail->table($table)->where($column, $mailbox->username)->count(), "{$table} still has rows");
        }

        $this->assertSame(0, $vmail->table('share_folder')->where('from_user', $mailbox->username)->count());
    }

    public function test_it_deletes_forwardings_that_point_at_the_removed_address(): void
    {
        $mailbox = Mailbox::factory()->create();
        DB::connection('vmail')->table('forwardings')->insert([
            'address' => 'team@other.test', 'forwarding' => $mailbox->username,
            'domain' => 'other.test', 'dest_domain' => $mailbox->domain, 'is_forwarding' => 1,
        ]);

        $this->service->delete($mailbox, 'admin@example.test');

        $this->assertSame(0, DB::connection('vmail')
            ->table('forwardings')->where('forwarding', $mailbox->username)->count());
    }

    public function test_a_failed_creation_leaves_nothing_behind(): void
    {
        Domain::factory()->create(['domain' => 'example.test', 'mailboxes' => 1]);
        $this->service->create(['username' => 'first@example.test', 'password' => 'x']);

        try {
            $this->service->create(['username' => 'second@example.test', 'password' => 'x']);
        } catch (RuntimeException) {
            // expected
        }

        $this->assertNull(Mailbox::query()->find('second@example.test'));
    }
}
