<?php

namespace App\Models\Vmail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Postfix's virtual alias map. One table serves three purposes, told apart by
 * the `is_*` flags:
 *
 * - `is_alias`: a recipient of an entry in the `alias` table.
 * - `is_forwarding`: a destination a mailbox owner forwards their mail to. A
 *   row whose `forwarding` equals its `address` is the "keep a copy in this
 *   mailbox" marker -- without it, mail is forwarded away and nothing is
 *   delivered locally.
 * - `is_list` / `is_maillist`: subscribers of a mailing list.
 *
 * @property string $address
 * @property string $forwarding
 */
class Forwarding extends Model
{
    protected $connection = 'vmail';

    protected $table = 'forwardings';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_maillist' => 'boolean',
            'is_list' => 'boolean',
            'is_forwarding' => 'boolean',
            'is_alias' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Forwarding>  $query
     * @return Builder<Forwarding>
     */
    public function scopeAliasMembers(Builder $query): Builder
    {
        return $query->where('is_alias', 1);
    }

    /**
     * @param  Builder<Forwarding>  $query
     * @return Builder<Forwarding>
     */
    public function scopeUserForwardings(Builder $query): Builder
    {
        return $query->where('is_forwarding', 1);
    }

    /** True for the row that keeps a copy in the sender's own mailbox. */
    public function isSelfCopy(): bool
    {
        return $this->address === $this->forwarding;
    }
}
