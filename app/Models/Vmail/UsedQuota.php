<?php

namespace App\Models\Vmail;

use Illuminate\Database\Eloquent\Model;

/**
 * Storage actually consumed, written by Dovecot's quota dict.
 * Read-only from this application's point of view.
 *
 * @property int $bytes Consumed storage in bytes -- note `mailbox.quota` is in megabytes.
 */
class UsedQuota extends Model
{
    protected $connection = 'vmail';

    protected $table = 'used_quota';

    protected $primaryKey = 'username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'bytes' => 'integer',
            'messages' => 'integer',
        ];
    }
}
