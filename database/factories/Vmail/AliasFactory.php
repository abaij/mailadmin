<?php

namespace Database\Factories\Vmail;

use App\Models\Vmail\Alias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alias>
 */
class AliasFactory extends Factory
{
    protected $model = Alias::class;

    public function definition(): array
    {
        $domain = $this->faker->unique()->domainName();

        return [
            'address' => $this->faker->unique()->userName().'@'.$domain,
            'domain' => $domain,
            'name' => $this->faker->word(),
            'expired' => '9999-12-31 00:00:00',
            'active' => 1,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (Alias $alias) => $alias->refresh());
    }

    public function inDomain(string $domain): static
    {
        return $this->state(fn (): array => [
            'address' => $this->faker->unique()->userName().'@'.$domain,
            'domain' => $domain,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => 0]);
    }
}
