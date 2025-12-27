<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            ['name' => 'Work', 'color' => '#3B82F6'],
            ['name' => 'Personal', 'color' => '#10B981'],
            ['name' => 'Shopping', 'color' => '#F59E0B'],
            ['name' => 'Health', 'color' => '#EF4444'],
            ['name' => 'Finance', 'color' => '#8B5CF6'],
            ['name' => 'Education', 'color' => '#06B6D4'],
            ['name' => 'Home', 'color' => '#EC4899'],
            ['name' => 'Travel', 'color' => '#14B8A6'],
            ['name' => 'Hobbies', 'color' => '#F97316'],
            ['name' => 'Family', 'color' => '#84CC16'],
        ];

        $category = fake()->randomElement($categories);

        return [
            'name' => $category['name'],
            'color' => $category['color'],
            'user_id' => User::factory(),
        ];
    }
}
