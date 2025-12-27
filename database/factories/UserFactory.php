<?php

namespace Database\Factories;

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
        $timezones = ['Asia/Jakarta', 'Asia/Singapore', 'UTC', 'America/New_York', 'Europe/London'];
        $colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
        $fontSizes = ['small', 'medium', 'large'];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar' => fake()->boolean(30) ? 'https://ui-avatars.com/api/?name=' . urlencode(fake()->name()) : null,
            'email_notifications' => fake()->boolean(80), // 80% enable notifications
            'timezone' => fake()->randomElement($timezones),
            'theme_color' => fake()->randomElement($colors),
            'font_size' => fake()->randomElement($fontSizes),
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
}
