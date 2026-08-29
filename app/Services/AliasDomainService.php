<?php

namespace App\Services;

use App\Models\Vmail\AliasDomain;
use App\Models\Vmail\Domain;
use App\Support\IRedMail\AddressRegistry;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Domain-level aliases: every address on the alias domain resolves to the same
 * local part on its target domain. No mailbox exists on the alias domain, so
 * nothing here touches storage.
 */
class AliasDomainService
{
    public function __construct(private readonly AddressRegistry $addresses) {}

    public function create(Domain $target, string $aliasDomain): AliasDomain
    {
        $name = Str::lower(trim($aliasDomain));

        $owner = $this->addresses->domainOwner($name);

        if ($owner === 'domain') {
            throw new RuntimeException("[{$name}] is already a mail domain of its own.");
        }

        if ($owner === 'alias_domain') {
            throw new RuntimeException("[{$name}] is already an alias domain.");
        }

        $now = now();

        $alias = AliasDomain::query()->create([
            'alias_domain' => $name,
            'target_domain' => $target->domain,
            'created' => $now,
            'modified' => $now,
            'active' => 1,
        ]);

        return $alias->refresh();
    }

    public function setActive(AliasDomain $aliasDomain, bool $active): AliasDomain
    {
        $aliasDomain->forceFill(['active' => $active])->save();

        return $aliasDomain;
    }

    public function delete(AliasDomain $aliasDomain): void
    {
        $aliasDomain->delete();
    }
}
