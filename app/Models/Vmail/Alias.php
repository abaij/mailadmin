<?php

namespace App\Models\Vmail;

use Database\Factories\Vmail\AliasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A mail alias: one address that fans out to several recipients.
 *
 * This table holds only the alias address itself. Its recipients live in
 * `forwardings`, as rows whose `address` is the alias and whose `is_alias`
 * flag is set.
 *
 * @property string $address
 */
class Alias extends VmailModel
{
    /** @use HasFactory<AliasFactory> */
    use HasFactory;

    protected $table = 'alias';

    protected $primaryKey = 'address';

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

    /** @return BelongsTo<Domain, $this> */
    public function domainRecord(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain', 'domain');
    }

    /** @return HasMany<Forwarding, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(Forwarding::class, 'address', 'address')->where('is_alias', 1);
    }

    public function localPart(): string
    {
        return substr($this->address, 0, (int) strrpos($this->address, '@'));
    }
}
