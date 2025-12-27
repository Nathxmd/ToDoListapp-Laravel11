<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create test data
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        
        Todo::factory()->count(5)->completed()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);
        
        Todo::factory()->count(3)->pending()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
        ]);
        
        Todo::factory()->count(2)->overdue()->create([
            'user_id' => $this->user->id,
            'priority' => 'medium',
        ]);
    }

    /**
     * Test getting dashboard statistics.
     */
    public function test_user_can_get_dashboard_stats(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary',
                'priority_breakdown',
                'category_breakdown',
                'completion_rate',
                'overdue_analysis',
                'recent_activity',
            ]);
    }

    /**
     * Test getting summary statistics.
     */
    public function test_user_can_get_summary_stats(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/stats/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_todos',
                    'completed_todos',
                    'pending_todos',
                    'overdue_todos',
                    'due_today',
                    'due_this_week',
                    'completion_percentage',
                ],
            ]);

        $this->assertEquals(10, $response->json('data.total_todos'));
        $this->assertEquals(5, $response->json('data.completed_todos'));
    }

    /**
     * Test getting priority breakdown.
     */
    public function test_user_can_get_priority_breakdown(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/stats/priority-breakdown');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'high',
                    'medium',
                    'low',
                    'total',
                ],
            ]);
    }

    /**
     * Test getting category breakdown.
     */
    public function test_user_can_get_category_breakdown(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/stats/category-breakdown');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'uncategorized',
                ],
            ]);
    }

    /**
     * Test getting activity timeline.
     */
    public function test_user_can_get_activity_timeline(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/stats/activity-timeline?days=7');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'date',
                        'day',
                        'created',
                        'completed',
                        'deleted',
                    ],
                ],
            ]);

        $this->assertCount(7, $response->json('data'));
    }

    /**
     * Test getting overdue analysis.
     */
    public function test_user_can_get_overdue_analysis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/stats/overdue-analysis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_overdue',
                    'by_priority',
                    'by_category',
                    'oldest_overdue',
                ],
            ]);

        $this->assertEquals(2, $response->json('data.total_overdue'));
    }

    /**
     * Test stats are user-specific.
     */
    public function test_stats_are_user_specific(): void
    {
        $otherUser = User::factory()->create();
        Todo::factory()->count(20)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/stats/summary');

        $response->assertStatus(200);
        
        // Should only see own todos (10), not other user's (20)
        $this->assertEquals(10, $response->json('data.total_todos'));
    }
}
