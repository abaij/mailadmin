<?php

namespace Database\Factories\Vmail;

use App\Models\Vmail\Mailbox;
use App\Support\IRedMail\MaildirPath;
use App\Support\IRedMail\PasswordHasher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mailbox>
 */
class MailboxFactory extends Factory
{
    protected $model = Mailbox::class;

    /** The plaintext behind every generated password, for use in assertions. */
    public const PASSWORD = 'correct-horse-battery-staple';

    public function definition(): array
    {
        $domain = $this->faker->unique()->domainName();
        $username = $this->faker->unique()->userName().'@'.$domain;

        return [
            'username' => $username,
            'password' => app(PasswordHasher::class)->make(self::PASSWORD),
            'name' => $this->faker->name(),
            'language' => 'en_US',
            'maildir' => app(MaildirPath::class)->generate($username),
            'quota' => 1024,
            'domain' => $domain,
            'expired' => '9999-12-31 00:00:00',
            'active' => 1,
        ];
    }

    /**
     * iRedMail's schema declares defaults for ~30 columns (the per-service
     * switches among them). Re-reading the row makes a created model carry
     * them, so tests see the same attributes a real page load would.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Mailbox $mailbox) => $mailbox->refresh());
    }

    public function inDomain(string $domain): static
    {
        return $this->state(fn (): array => [
            'username' => $this->faker->unique()->userName().'@'.$domain,
            'domain' => $domain,
        ])->afterMaking(function (Mailbox $mailbox): void {
            $mailbox->maildir = app(MaildirPath::class)->generate($mailbox->username);
        });
    }

    public function globalAdmin(): static
    {
        return $this->state(['isadmin' => 1, 'isglobaladmin' => 1]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => 0]);
    }

    public function expired(): static
    {
        return $this->state(['expired' => now()->subDay()->format('Y-m-d H:i:s')]);
    }
}
