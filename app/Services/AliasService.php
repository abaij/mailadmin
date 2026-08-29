<?php

namespace App\Services;

use App\Models\Vmail\Alias;
use App\Models\Vmail\Domain;
use App\Models\Vmail\Forwarding;
use App\Support\IRedMail\AddressRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Manages mail aliases and the recipient rows that back them.
 *
 * An alias is two things at once: a row in `alias` that makes Postfix accept
 * the address, and one row per recipient in `forwardings`. Writing only the
 * first creates an address that accepts mail and then drops it.
 */
class AliasService
{
    public function __construct(private readonly AddressRegistry $addresses) {}

    /**
     * @param  array{address: string, name?: string, active?: bool}  $attributes
     * @param  list<string>  $members
     */
    public function create(array $attributes, array $members = []): Alias
    {
        $address = Str::lower(trim($attributes['address']));
        $domainName = Str::after($address, '@');

        $domain = Domain::query()->find($domainName);

        if ($domain === null) {
            throw new RuntimeException("Domain [{$domainName}] does not exist.");
        }

        return DB::connection('vmail')->transaction(function () use ($attributes, $address, $domain, $members): Alias {
            $owner = $this->addresses->addressOwner($address);

            if ($owner !== null) {
                throw new RuntimeException("[{$address}] already exists as a {$owner}.");
            }

            $this->guardAgainstAliasLimit($domain);

            $now = now();

            $alias = Alias::query()->create([
                ...$attributes,
                'address' => $address,
                'domain' => $domain->domain,
                'active' => $attributes['active'] ?? 1,
                'created' => $now,
                'modified' => $now,
                'expired' => '9999-12-31 00:00:00',
            ]);

            $this->syncMembers($alias, $members);

            return $alias->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>|null  $members  Null leaves the recipients untouched.
     */
    public function update(Alias $alias, array $attributes, ?array $members = null): Alias
    {
        // The address is the primary key and is referenced by every recipient
        // row, so it is fixed once created.
        unset($attributes['address'], $attributes['domain']);

        return DB::connection('vmail')->transaction(function () use ($alias, $attributes, $members): Alias {
            $alias->fill($attributes)->save();

            if ($members !== null) {
                $this->syncMembers($alias, $members);
            }

            return $alias;
        });
    }

    public function setActive(Alias $alias, bool $active): Alias
    {
        $alias->forceFill(['active' => $active])->save();

        return $alias;
    }

    public function delete(Alias $alias): void
    {
        DB::connection('vmail')->transaction(function () use ($alias): void {
            $alias->members()->delete();
            $alias->delete();
        });
    }

    /**
     * Replaces the alias's recipients with exactly this list.
     *
     * @param  list<string>  $members
     */
    public function syncMembers(Alias $alias, array $members): void
    {
        $wanted = $this->normaliseMembers($members, $alias->address);

        DB::connection('vmail')->transaction(function () use ($alias, $wanted): void {
            $existing = $alias->members()->pluck('forwarding')->all();

            $alias->members()
                ->whereNotIn('forwarding', $wanted)
                ->delete();

            foreach (array_diff($wanted, $existing) as $member) {
                Forwarding::query()->create([
                    'address' => $alias->address,
                    'forwarding' => $member,
                    'domain' => $alias->domain,
                    'dest_domain' => Str::after($member, '@'),
                    'is_alias' => 1,
                    'active' => 1,
                ]);
            }
        });
    }

    /**
     * @param  list<string>  $members
     * @return list<string>
     */
    private function normaliseMembers(array $members, string $address): array
    {
        $self = Str::lower($address);

        $normalised = array_map(
            fn (string $member): string => Str::lower(trim($member)),
            $members,
        );

        return array_values(array_unique(array_filter(
            $normalised,
            // An alias listing itself would loop mail back through Postfix.
            fn (string $member): bool => $member !== '' && $member !== $self,
        )));
    }

    private function guardAgainstAliasLimit(Domain $domain): void
    {
        // Zero means "no limit", the same convention as the mailbox count.
        if ($domain->aliases <= 0) {
            return;
        }

        $current = Alias::query()->where('domain', $domain->domain)->count();

        if ($current >= $domain->aliases) {
            throw new RuntimeException(
                "Domain [{$domain->domain}] already holds its maximum of {$domain->aliases} aliases."
            );
        }
    }
}
