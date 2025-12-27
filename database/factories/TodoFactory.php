<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Todo>
 */
class TodoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priorities = ['low', 'medium', 'high'];
        $isCompleted = fake()->boolean(40); // 40% completed
        
        // Generate realistic due dates
        $dueDate = fake()->boolean(70) ? fake()->dateTimeBetween('-7 days', '+30 days') : null;
        
        // Check if overdue
        $isOverdue = false;
        if ($dueDate && !$isCompleted && Carbon::parse($dueDate)->isPast()) {
            $isOverdue = true;
        }

        return [
            'title' => fake()->sentence(rand(3, 8)),
            'description' => fake()->boolean(70) ? fake()->paragraph(rand(1, 3)) : null,
            'priority' => fake()->randomElement($priorities),
            'due_date' => $dueDate,
            'is_completed' => $isCompleted,
            'is_overdue' => $isOverdue,
            'category_id' => null, // Will be set in seeder
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the todo is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
            'is_overdue' => false,
        ]);
    }

    /**
     * Indicate that the todo is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
        ]);
    }

    /**
     * Indicate that the todo is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
            'is_overdue' => true,
            'due_date' => fake()->dateTimeBetween('-14 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the todo is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the todo is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => Carbon::today()->addHours(rand(1, 23)),
        ]);
    }
}
