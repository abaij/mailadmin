<?php

namespace App\Models\Vmail;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for every table owned by iRedMail's `vmail` database.
 *
 * These tables are created and upgraded by iRedMail itself, so they are never
 * covered by this application's migrations.
 */
abstract class VmailModel extends Model
{
    protected $connection = 'vmail';

    public $incrementing = false;

    protected $keyType = 'string';

    /** iRedMail names its timestamp columns `created` / `modified`. */
    const CREATED_AT = 'created';

    const UPDATED_AT = 'modified';

    /**
     * iRedMail writes this instead of NULL when a timestamp is unknown --
     * rows imported or created outside iRedAdmin carry it.
     */
    private const UNKNOWN_TIMESTAMP = '1970-01-01 01:01:01';

    /**
     * A timestamp column, or null when it still holds iRedMail's placeholder.
     */
    public function timestamp(string $column): ?CarbonInterface
    {
        $value = $this->{$column};

        if (! $value instanceof CarbonInterface) {
            return null;
        }

        return $value->format('Y-m-d H:i:s') === self::UNKNOWN_TIMESTAMP ? null : $value;
    }

    public function createdAt(): ?CarbonInterface
    {
        return $this->timestamp(self::CREATED_AT);
    }
}
