<?php

namespace App\Support\Scoping;

use App\Models\Vmail\Mailbox;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts a query to the domains the signed-in administrator may manage.
 *
 * A global administrator sees everything; a domain administrator sees only the
 * domains granted in `domain_admins`. Anyone else sees nothing, so a missing
 * check fails closed rather than leaking another tenant's accounts.
 */
class ManagedDomains
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  string  $column  Column holding the domain name on the queried table.
     * @return Builder<TModel>
     */
    public static function scope(Builder $query, string $column = 'domain'): Builder
    {
        $user = Auth::user();

        if ($user instanceof Mailbox && $user->isGlobalAdmin()) {
            return $query;
        }

        $domains = $user instanceof Mailbox ? $user->managedDomains() : [];

        return $query->whereIn($query->qualifyColumn($column), $domains);
    }
}
