<?php

namespace App\Services;

use App\Models\Vmail\DeletedMailbox;
use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Models\Vmail\UsedQuota;
use App\Support\IRedMail\AddressRegistry;
use App\Support\IRedMail\MaildirPath;
use App\Support\IRedMail\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Every write to the `mailbox` table goes through here, so the bookkeeping
 * iRedMail expects -- hashed maildir paths, the deletion queue, the related
 * per-user rows -- happens in one place and inside a transaction.
 */
class MailboxService
{
    /**
     * Tables keyed by a plain `username` column that must be cleaned up when an
     * account is removed.
     */
    private const USER_KEYED_TABLES = [
        'used_quota',
        'last_login',
        'recipient_bcc_user',
        'sender_bcc_user',
    ];

    public function __construct(
        private readonly PasswordHasher $hasher,
        private readonly MaildirPath $maildirPaths,
        private readonly AddressRegistry $addresses,
    ) {}

    /**
     * @param  array{username: string, password: string, name?: string, quota?: int, language?: string, ...}  $attributes
     */
    public function create(array $attributes): Mailbox
    {
        $username = Str::lower(trim($attributes['username']));
        $domainName = Str::after($username, '@');

        $domain = Domain::query()->find($domainName);

        if ($domain === null) {
            throw new RuntimeException("Domain [{$domainName}] does not exist.");
        }

        return DB::connection('vmail')->transaction(function () use ($attributes, $username, $domain): Mailbox {
            $this->guardAgainstMailboxLimit($domain);

            // An address must be unique across mailboxes, aliases and mailing
            // lists -- nothing in the schema enforces that.
            $owner = $this->addresses->addressOwner($username);

            if ($owner !== null) {
                throw new RuntimeException("[{$username}] already exists as a {$owner}.");
            }

            $now = now();

            $mailbox = Mailbox::query()->create([
                ...$attributes,
                'username' => $username,
                'domain' => $domain->domain,
                'password' => $this->hasher->make($attributes['password']),
                'maildir' => $this->maildirPaths->generate($username, $now),
                'quota' => $attributes['quota'] ?? 0,
                'language' => $attributes['language'] ?? 'en_US',
                'active' => $attributes['active'] ?? 1,
                'passwordlastchange' => $now,
                'created' => $now,
                'modified' => $now,
                'expired' => '9999-12-31 00:00:00',
            ]);

            // Re-read so the returned model carries the many column defaults
            // iRedMail declares in the schema rather than nulls.
            return $mailbox->refresh();
        });
    }

    /**
     * Sets a new password, keeping whatever hashing scheme the account already
     * uses so an update never migrates a row to a different scheme.
     */
    public function changePassword(Mailbox $mailbox, string $password): Mailbox
    {
        $mailbox->forceFill([
            'password' => $this->hasher->makeMatching($password, $mailbox->password),
            'passwordlastchange' => now(),
        ])->save();

        return $mailbox;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Mailbox $mailbox, array $attributes): Mailbox
    {
        // The address and its derived columns are immutable: renaming an
        // account would orphan the maildir already on disk.
        unset($attributes['username'], $attributes['domain'], $attributes['maildir'], $attributes['password']);

        $mailbox->fill($attributes)->save();

        return $mailbox;
    }

    public function setActive(Mailbox $mailbox, bool $active): Mailbox
    {
        $mailbox->forceFill(['active' => $active])->save();

        return $mailbox;
    }

    /**
     * Removes the account and queues its maildir for the `cleanup_maildir`
     * cron job. Without the queue row the mail files stay on disk forever.
     *
     * @param  int|null  $keepDays  Days to keep the maildir before deletion; null deletes on the next run.
     */
    public function delete(Mailbox $mailbox, string $deletedBy, ?int $keepDays = null): void
    {
        DB::connection('vmail')->transaction(function () use ($mailbox, $deletedBy, $keepDays): void {
            $used = UsedQuota::query()->whereKey($mailbox->username)->first();

            DeletedMailbox::query()->create([
                'username' => $mailbox->username,
                'domain' => $mailbox->domain,
                'maildir' => $mailbox->maildir,
                'bytes' => $used !== null ? $used->bytes : 0,
                'messages' => $used !== null ? $used->messages : 0,
                'admin' => $deletedBy,
                'delete_date' => $keepDays === null ? now()->toDateString() : now()->addDays($keepDays)->toDateString(),
            ]);

            $this->purgeRelatedRows($mailbox->username);

            $mailbox->delete();
        });
    }

    /**
     * Deletes rows in the other tables that key off this address. Left behind,
     * they would resurrect as dangling aliases or phantom quota entries when
     * the address is recreated.
     */
    private function purgeRelatedRows(string $username): void
    {
        $connection = DB::connection('vmail');

        foreach (self::USER_KEYED_TABLES as $table) {
            $connection->table($table)->where('username', $username)->delete();
        }

        $connection->table('forwardings')
            ->where('address', $username)
            ->orWhere('forwarding', $username)
            ->delete();

        $connection->table('share_folder')
            ->where('from_user', $username)
            ->orWhere('to_user', $username)
            ->delete();

        $connection->table('anyone_shares')->where('from_user', $username)->delete();
        $connection->table('moderators')->where('moderator', $username)->delete();
        $connection->table('maillist_owners')->where('owner', $username)->delete();
        $connection->table('sender_relayhost')->where('account', $username)->delete();
        $connection->table('domain_admins')->where('username', $username)->delete();
    }

    private function guardAgainstMailboxLimit(Domain $domain): void
    {
        if (! $domain->hasMailboxLimit()) {
            return;
        }

        $current = Mailbox::query()->where('domain', $domain->domain)->count();

        if ($current >= $domain->mailboxes) {
            throw new RuntimeException(
                "Domain [{$domain->domain}] already holds its maximum of {$domain->mailboxes} mailboxes."
            );
        }
    }
}
