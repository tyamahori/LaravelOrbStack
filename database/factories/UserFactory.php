<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    #[Override]
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => Carbon::now(),
            'password' => new BcryptHasher()->make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this
            ->state(
                static fn (array $attributes): array => [
                    'email_verified_at' => null,
                ]
            );
    }
}
