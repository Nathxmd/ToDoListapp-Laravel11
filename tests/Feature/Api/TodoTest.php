<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test creating a todo.
     */
    public function test_user_can_create_todo(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/todos', [
                'title' => 'Test Todo',
                'description' => 'Test Description',
                'priority' => 'high',
                'due_date' => now()->addDays(7)->toDateTimeString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'priority',
                    'due_date',
                    'is_completed',
                ],
            ]);

        $this->assertDatabaseHas('todos', [
            'title' => 'Test Todo',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test listing todos.
     */
    public function test_user_can_list_todos(): void
    {
        Todo::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/todos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta',
                'links',
            ]);
    }

    /**
     * Test filtering todos by status.
     */
    public function test_user_can_filter_todos_by_status(): void
    {
        Todo::factory()->count(3)->completed()->create(['user_id' => $this->user->id]);
        Todo::factory()->count(2)->pending()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/todos?status=completed');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test searching todos.
     */
    public function test_user_can_search_todos(): void
    {
        Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Buy groceries',
        ]);
        Todo::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Clean house',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/todos?search=groceries');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /**
     * Test updating a todo.
     */
    public function test_user_can_update_todo(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/todos/{$todo->id}", [
                'title' => 'Updated Title',
                'priority' => 'low',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo updated successfully',
            ]);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Updated Title',
            'priority' => 'low',
        ]);
    }

    /**
     * Test completing a todo.
     */
    public function test_user_can_complete_todo(): void
    {
        $todo = Todo::factory()->pending()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/todos/{$todo->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo marked as completed',
            ]);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'is_completed' => true,
        ]);
    }

    /**
     * Test soft deleting a todo.
     */
    public function test_user_can_soft_delete_todo(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/todos/{$todo->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo moved to trash',
            ]);

        $this->assertSoftDeleted('todos', [
            'id' => $todo->id,
        ]);
    }

    /**
     * Test restoring a deleted todo.
     */
    public function test_user_can_restore_todo(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);
        $todo->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/todos/{$todo->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Todo restored successfully',
            ]);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test getting trashed todos.
     */
    public function test_user_can_get_trashed_todos(): void
    {
        $todo = Todo::factory()->create(['user_id' => $this->user->id]);
        $todo->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/todos/trash');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta',
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test exporting todos as JSON.
     */
    public function test_user_can_export_todos_as_json(): void
    {
        Todo::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/todos/export?format=json');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'exported_at',
                'total',
            ]);

        $this->assertEquals(3, $response->json('total'));
    }

    /**
     * Test user cannot access other user's todos.
     */
    public function test_user_cannot_access_other_users_todos(): void
    {
        $otherUser = User::factory()->create();
        $otherTodo = Todo::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/todos');

        $response->assertStatus(200);
        $todos = collect($response->json('data'));
        
        $this->assertFalse($todos->contains('id', $otherTodo->id));
    }
}
