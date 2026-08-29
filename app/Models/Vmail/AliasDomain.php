<?php

namespace App\Models\Vmail;

use Database\Factories\Vmail\AliasDomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A whole domain that mirrors another: mail to `user@alias_domain` is delivered
 * to `user@target_domain`. No mailbox exists on the alias domain itself.
 *
 * @property string $alias_domain
 * @property string $target_domain
 */
class AliasDomain extends VmailModel
{
    /** @use HasFactory<AliasDomainFactory> */
    use HasFactory;

    protected $table = 'alias_domain';

    protected $primaryKey = 'alias_domain';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'created' => 'datetime',
            'modified' => 'datetime',
        ];
    }

    /** @return BelongsTo<Domain, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'target_domain', 'domain');
    }
}
