<?php

namespace App\Models\Vmail;

use Illuminate\Database\Eloquent\Model;

/**
 * Queue read by iRedMail's `cleanup_maildir` cron job, which removes the
 * maildir from disk. Deleting a `mailbox` row without queueing here leaves the
 * mail files orphaned on the server.
 */
class DeletedMailbox extends Model
{
    protected $connection = 'vmail';

    protected $table = 'deleted_mailboxes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'bytes' => 'integer',
            'messages' => 'integer',
            'delete_date' => 'date',
        ];
    }
}
