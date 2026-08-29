<?php

namespace App\Services;

use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Support\IRedMail\AddressRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes to the `domain` table, and tears down everything that hangs off a
 * domain when it is removed.
 */
class DomainService
{
    public function __construct(
        private readonly MailboxService $mailboxes,
        private readonly AddressRegistry $addresses,
    ) {}

    /**
     * @param  array{domain: string, description?: string, maxquota?: int, mailboxes?: int, ...}  $attributes
     */
    public function create(array $attributes): Domain
    {
        $name = Str::lower(trim($attributes['domain']));

        $owner = $this->domainOwnerLabel($name);

        if ($owner !== null) {
            throw new RuntimeException($owner);
        }

        $now = now();

        $domain = Domain::query()->create([
            ...$attributes,
            'domain' => $name,
            'transport' => $attributes['transport'] ?? 'dovecot',
            'active' => $attributes['active'] ?? 1,
            'created' => $now,
            'modified' => $now,
            'expired' => '9999-12-31 00:00:00',
        ]);

        // Re-read so the returned model carries the schema's column defaults.
        return $domain->refresh();
    }

    /**
     * A human-readable reason the name cannot be used, or null when it is free.
     */
    private function domainOwnerLabel(string $name): ?string
    {
        return match ($this->addresses->domainOwner($name)) {
            'domain' => "Domain [{$name}] already exists.",
            'alias_domain' => "[{$name}] is already used as an alias domain.",
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Domain $domain, array $attributes): Domain
    {
        // Renaming a domain would break every mailbox address and maildir
        // path under it, so the key is fixed once created.
        unset($attributes['domain']);

        $domain->fill($attributes)->save();

        return $domain;
    }

    public function setActive(Domain $domain, bool $active): Domain
    {
        $domain->forceFill(['active' => $active])->save();

        return $domain;
    }

    /**
     * Removes a domain along with its mailboxes, aliases and admin grants.
     * Each mailbox goes through {@see MailboxService::delete()} so its maildir
     * is queued for cleanup rather than orphaned.
     */
    public function delete(Domain $domain, string $deletedBy, ?int $keepDays = null): void
    {
        DB::connection('vmail')->transaction(function () use ($domain, $deletedBy, $keepDays): void {
            Mailbox::query()->where('domain', $domain->domain)->cursor()
                ->each(fn (Mailbox $mailbox) => $this->mailboxes->delete($mailbox, $deletedBy, $keepDays));

            $connection = DB::connection('vmail');

            // `forwardings` holds the recipient rows of this domain's aliases;
            // without this they outlive the alias they belonged to. Rows whose
            // `dest_domain` is this domain belong to *other* domains' aliases,
            // so they are left for those admins rather than rewritten here.
            foreach ([
                'alias',
                'forwardings',
                'maillists',
                'maillist_owners',
                'moderators',
                'recipient_bcc_domain',
                'sender_bcc_domain',
                'domain_admins',
            ] as $table) {
                $connection->table($table)->where('domain', $domain->domain)->delete();
            }

            $connection->table('alias_domain')->where('target_domain', $domain->domain)->delete();

            $domain->delete();
        });
    }
}
