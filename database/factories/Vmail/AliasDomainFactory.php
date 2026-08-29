<?php

namespace Database\Factories\Vmail;

use App\Models\Vmail\AliasDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AliasDomain>
 */
class AliasDomainFactory extends Factory
{
    protected $model = AliasDomain::class;

    public function definition(): array
    {
        return [
            'alias_domain' => $this->faker->unique()->domainName(),
            'target_domain' => $this->faker->unique()->domainName(),
            'active' => 1,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (AliasDomain $aliasDomain) => $aliasDomain->refresh());
    }

    public function targeting(string $domain): static
    {
        return $this->state(['target_domain' => $domain]);
    }
}
