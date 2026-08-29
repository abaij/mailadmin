<?php

namespace App\Support\IRedMail;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Answers whether an email address or domain name is already claimed.
 *
 * iRedMail spreads addresses across three tables with no foreign keys between
 * them, so nothing at the database level stops an alias from shadowing a
 * mailbox. Postfix would then resolve the address through whichever map it
 * consults first, silently delivering mail to the wrong place.
 */
class AddressRegistry
{
    /**
     * What already owns this address, or null when it is free.
     *
     * @return 'mailbox'|'alias'|'maillist'|null
     */
    public function addressOwner(string $address, ?string $ignore = null): ?string
    {
        $address = Str::lower(trim($address));

        if ($ignore !== null && $address === Str::lower(trim($ignore))) {
            return null;
        }

        $connection = DB::connection('vmail');

        foreach (['mailbox' => 'username', 'alias' => 'address', 'maillists' => 'address'] as $table => $column) {
            if ($connection->table($table)->where($column, $address)->exists()) {
                return $table === 'maillists' ? 'maillist' : $table;
            }
        }

        return null;
    }

    public function addressIsAvailable(string $address, ?string $ignore = null): bool
    {
        return $this->addressOwner($address, $ignore) === null;
    }

    /**
     * What already owns this domain name, or null when it is free.
     *
     * @return 'domain'|'alias_domain'|null
     */
    public function domainOwner(string $domain, ?string $ignore = null): ?string
    {
        $domain = Str::lower(trim($domain));

        if ($ignore !== null && $domain === Str::lower(trim($ignore))) {
            return null;
        }

        $connection = DB::connection('vmail');

        if ($connection->table('domain')->where('domain', $domain)->exists()) {
            return 'domain';
        }

        if ($connection->table('alias_domain')->where('alias_domain', $domain)->exists()) {
            return 'alias_domain';
        }

        return null;
    }

    public function domainIsAvailable(string $domain, ?string $ignore = null): bool
    {
        return $this->domainOwner($domain, $ignore) === null;
    }
}
