<?php

namespace Database\Factories\Vmail;

use App\Models\Vmail\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        return [
            'domain' => $this->faker->unique()->domainName(),
            'description' => $this->faker->company(),
            'aliases' => 0,
            'mailboxes' => 0,
            'maillists' => 0,
            'maxquota' => 0,
            'quota' => 0,
            'transport' => 'dovecot',
            'backupmx' => 0,
            'expired' => '9999-12-31 00:00:00',
            'active' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => 0]);
    }
}
