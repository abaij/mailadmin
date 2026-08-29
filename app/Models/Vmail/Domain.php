<?php

namespace App\Models\Vmail;

use Database\Factories\Vmail\DomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $domain
 * @property int $maxquota Domain-wide quota ceiling, in megabytes.
 */
class Domain extends VmailModel
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory;

    protected $table = 'domain';

    protected $primaryKey = 'domain';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'aliases' => 'integer',
            'mailboxes' => 'integer',
            'maillists' => 'integer',
            'maxquota' => 'integer',
            'quota' => 'integer',
            'backupmx' => 'boolean',
            'active' => 'boolean',
            'created' => 'datetime',
            'modified' => 'datetime',
            'expired' => 'datetime',
        ];
    }

    /** @return HasMany<Mailbox, $this> */
    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class, 'domain', 'domain');
    }

    /** @return HasMany<Alias, $this> */
    public function aliases(): HasMany
    {
        return $this->hasMany(Alias::class, 'domain', 'domain');
    }

    /** @return HasMany<AliasDomain, $this> */
    public function aliasDomains(): HasMany
    {
        return $this->hasMany(AliasDomain::class, 'target_domain', 'domain');
    }

    /** @return HasMany<DomainAdmin, $this> */
    public function admins(): HasMany
    {
        return $this->hasMany(DomainAdmin::class, 'domain', 'domain');
    }

    /** Zero means "no limit" in iRedMail, for both mailbox count and quota. */
    public function hasMailboxLimit(): bool
    {
        return $this->mailboxes > 0;
    }
}
