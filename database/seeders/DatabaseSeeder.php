<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Todo;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding database...');

        // Create demo user for testing
        $this->command->info('Creating demo user...');
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@todoapp.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'avatar' => 'https://ui-avatars.com/api/?name=Demo+User&background=3B82F6&color=fff',
            'email_notifications' => true,
            'timezone' => 'Asia/Jakarta',
            'theme_color' => '#3B82F6',
            'font_size' => 'medium',
        ]);

        // Create categories for demo user
        $this->command->info('Creating categories for demo user...');
        $categories = [
            ['name' => 'Work', 'color' => '#3B82F6'],
            ['name' => 'Personal', 'color' => '#10B981'],
            ['name' => 'Shopping', 'color' => '#F59E0B'],
            ['name' => 'Health', 'color' => '#EF4444'],
            ['name' => 'Finance', 'color' => '#8B5CF6'],
        ];

        $demoCategories = [];
        foreach ($categories as $category) {
            $demoCategories[] = Category::create([
                'name' => $category['name'],
                'color' => $category['color'],
                'user_id' => $demoUser->id,
            ]);
        }

        // Create todos for demo user
        $this->command->info('Creating todos for demo user...');
        
        $categoryIds = collect($demoCategories)->pluck('id')->toArray();
        
        // Create 10 simple todos
        for ($i = 0; $i < 10; $i++) {
            Todo::create([
                'title' => 'Sample Todo ' . ($i + 1),
                'description' => 'This is a sample todo description',
                'priority' => ['low', 'medium', 'high'][array_rand(['low', 'medium', 'high'])],
                'due_date' => now()->addDays(rand(1, 30)),
                'is_completed' => rand(0, 1) == 1,
                'is_overdue' => false,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'user_id' => $demoUser->id,
            ]);
        }

        $this->command->info('');
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('   - Total Users: ' . User::count());
        $this->command->info('   - Total Categories: ' . Category::count());
        $this->command->info('   - Total Todos: ' . Todo::count());
        $this->command->info('');
        $this->command->info('✅ Demo user: demo@todoapp.com / password');
    }
}
