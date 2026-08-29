<?php

namespace App\Models\Vmail;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-protocol last login, written by Dovecot as Unix timestamps.
 */
class LastLogin extends Model
{
    protected $connection = 'vmail';

    protected $table = 'last_login';

    protected $primaryKey = 'username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'imap' => 'integer',
            'pop3' => 'integer',
            'lda' => 'integer',
        ];
    }

    /** The most recent login across all protocols, or null if never seen. */
    public function latest(): ?CarbonImmutable
    {
        $stamps = array_filter([$this->imap, $this->pop3, $this->lda]);

        return $stamps === [] ? null : CarbonImmutable::createFromTimestamp(max($stamps));
    }
}
