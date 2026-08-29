<?php

namespace App\Models\Vmail;

use Database\Factories\Vmail\MailboxFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

/**
 * A mail account. Doubles as the authenticatable user for this application --
 * admins sign in with their existing iRedMail credentials.
 *
 * @property string $username Full email address; the primary key.
 * @property int $quota Allotted storage, in megabytes. Zero means unlimited.
 */
class Mailbox extends VmailModel implements Authenticatable
{
    /** @use HasFactory<MailboxFactory> */
    use HasFactory;

    use Notifiable;

    /** Megabytes, the unit iRedMail uses for `mailbox.quota`. */
    public const QUOTA_UNIT_BYTES = 1048576;

    protected $table = 'mailbox';

    protected $primaryKey = 'username';

    protected $guarded = [];

    protected $hidden = ['password'];

    /**
     * Per-service switches iRedMail exposes on each account. Grouped so the UI
     * can offer coarse toggles instead of 25 checkboxes.
     *
     * @var array<string, list<string>>
     */
    public const SERVICE_GROUPS = [
        'smtp' => ['enablesmtp', 'enablesmtpsecured'],
        'pop3' => ['enablepop3', 'enablepop3secured', 'enablepop3tls'],
        'imap' => ['enableimap', 'enableimapsecured', 'enableimaptls'],
        'sieve' => ['enablesieve', 'enablesievesecured', 'enablesievetls', 'enablemanagesieve', 'enablemanagesievesecured'],
        'sogo' => ['enablesogo', 'enablesogowebmail', 'enablesogocalendar', 'enablesogoactivesync'],
    ];

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'isadmin' => 'boolean',
            'isglobaladmin' => 'boolean',
            'active' => 'boolean',
            'created' => 'datetime',
            'modified' => 'datetime',
            'expired' => 'datetime',
            'passwordlastchange' => 'datetime',
        ];
    }

    /** @return BelongsTo<Domain, $this> */
    public function domainRecord(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain', 'domain');
    }

    /** @return HasOne<UsedQuota, $this> */
    public function usedQuota(): HasOne
    {
        return $this->hasOne(UsedQuota::class, 'username', 'username');
    }

    /** @return HasOne<LastLogin, $this> */
    public function lastLogin(): HasOne
    {
        return $this->hasOne(LastLogin::class, 'username', 'username');
    }

    /** @return HasMany<Forwarding, $this> */
    public function forwardings(): HasMany
    {
        return $this->hasMany(Forwarding::class, 'address', 'username');
    }

    /**
     * Domains this account administers, empty for a plain user.
     *
     * @return HasMany<DomainAdmin, $this>
     */
    public function administeredDomains(): HasMany
    {
        return $this->hasMany(DomainAdmin::class, 'username', 'username');
    }

    /**
     * Global administrators see every domain. iRedMail records this either on
     * the mailbox itself or as a `domain_admins` row against the literal
     * domain `ALL`.
     */
    public function isGlobalAdmin(): bool
    {
        return $this->isglobaladmin
            || $this->administeredDomains()
                ->where('active', 1)
                ->where('domain', DomainAdmin::ALL_DOMAINS)
                ->exists();
    }

    public function isDomainAdmin(): bool
    {
        return $this->isGlobalAdmin() || $this->managedDomains() !== [];
    }

    /**
     * Domains this account may administer. Empty for a global admin, who is
     * not restricted to a list -- check {@see isGlobalAdmin()} first.
     *
     * @return list<string>
     */
    public function managedDomains(): array
    {
        return array_values(
            $this->administeredDomains()
                ->where('active', 1)
                ->where('domain', '!=', DomainAdmin::ALL_DOMAINS)
                ->get()
                ->map(fn (DomainAdmin $grant): string => $grant->domain)
                ->all()
        );
    }

    public function canManageDomain(string $domain): bool
    {
        return $this->isGlobalAdmin() || in_array(strtolower($domain), $this->managedDomains(), true);
    }

    public function localPart(): string
    {
        return substr($this->username, 0, (int) strrpos($this->username, '@'));
    }

    /** Allotted quota in bytes, or null when the account is unlimited. */
    public function quotaBytes(): ?int
    {
        return $this->quota > 0 ? $this->quota * self::QUOTA_UNIT_BYTES : null;
    }

    public function routeNotificationForMail(): string
    {
        return $this->username;
    }

    // --- Authenticatable -------------------------------------------------
    // Backed by iRedMail's own password column, so there is no
    // "remember me" token column and none is added.

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function getAuthIdentifier(): string
    {
        return $this->username;
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // iRedMail's schema has no column for this.
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }
}
