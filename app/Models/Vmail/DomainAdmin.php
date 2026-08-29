<?php

namespace App\Models\Vmail;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grants an account admin rights over one domain. iRedMail uses the literal
 * domain value `ALL` to mean "every domain".
 */
class DomainAdmin extends VmailModel
{
    /** Sentinel stored in the `domain` column for a global administrator. */
    public const ALL_DOMAINS = 'ALL';

    protected $table = 'domain_admins';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'created' => 'datetime',
            'modified' => 'datetime',
            'expired' => 'datetime',
        ];
    }

    /** @return BelongsTo<Mailbox, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class, 'username', 'username');
    }

    /** @return BelongsTo<Domain, $this> */
    public function domainRecord(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain', 'domain');
    }
}
