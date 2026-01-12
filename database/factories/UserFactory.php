<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '+48' . fake()->numerify('#########'),
            'pesel' => $this->generatePesel(),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country' => 'Polska',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user account is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
        ]);
    }

    /**
     * Indicate that the user account is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Generate a random PESEL number (simplified version)
     */
    private function generatePesel(): string
    {
        // Simplified PESEL generation - only for testing
        // In production, use proper PESEL validation algorithm
        return str_pad(fake()->unique()->numberBetween(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
    }
}
