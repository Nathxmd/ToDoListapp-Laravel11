<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Todo\FilterTodoRequest;
use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Http\Resources\TodoCollection;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TodoController extends Controller
{
    /**
     * Display a listing of todos with advanced filtering.
     *
     * @param FilterTodoRequest $request
     * @return JsonResponse
     */
    public function index(FilterTodoRequest $request): JsonResponse
    {
        $query = Todo::query()
            ->where('user_id', $request->user()->id)
            ->with(['category']);

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            match ($request->status) {
                'completed' => $query->completed(),
                'pending' => $query->pending(),
                default => null,
            };
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by due date
        if ($request->filled('due')) {
            match ($request->due) {
                'today' => $query->dueToday(),
                'week' => $query->dueThisWeek(),
                'overdue' => $query->overdue(),
                'upcoming' => $query->where('due_date', '>', now())->where('is_completed', false),
                default => null,
            };
        }

        // Sort
        $query->orderBy('is_completed', 'asc')
              ->orderBy('priority', 'desc')
              ->orderBy('due_date', 'asc')
              ->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $request->input('per_page', 15);
        $todos = $query->paginate($perPage);

        return response()->json([
            'data' => TodoResource::collection($todos),
            'meta' => [
                'current_page' => $todos->currentPage(),
                'last_page' => $todos->lastPage(),
                'per_page' => $todos->perPage(),
                'total' => $todos->total(),
                'from' => $todos->firstItem(),
                'to' => $todos->lastItem(),
            ],
            'links' => [
                'first' => $todos->url(1),
                'last' => $todos->url($todos->lastPage()),
                'prev' => $todos->previousPageUrl(),
                'next' => $todos->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created todo.
     *
     * @param StoreTodoRequest $request
     * @return JsonResponse
     */
    public function store(StoreTodoRequest $request): JsonResponse
    {
        $todo = Todo::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'priority' => $request->input('priority', 'medium'),
        ]);

        return response()->json([
            'message' => 'Todo created successfully',
            'data' => new TodoResource($todo->load('category')),
        ], 201);
    }

    /**
     * Display the specified todo.
     *
     * @param Todo $todo
     * @return JsonResponse
     */
    public function show(Todo $todo): JsonResponse
    {
        // Authorization check will be done by middleware
        return response()->json([
            'data' => new TodoResource($todo->load('category')),
        ]);
    }

    /**
     * Update the specified todo.
     *
     * @param UpdateTodoRequest $request
     * @param Todo $todo
     * @return JsonResponse
     */
    public function update(UpdateTodoRequest $request, Todo $todo): JsonResponse
    {
        $todo->update($request->validated());

        // Check if todo is overdue
        $todo->checkOverdue();

        return response()->json([
            'message' => 'Todo updated successfully',
            'data' => new TodoResource($todo->fresh()->load('category')),
        ]);
    }

    /**
     * Remove the specified todo (soft delete).
     *
     * @param Todo $todo
     * @return JsonResponse
     */
    public function destroy(Todo $todo): JsonResponse
    {
        $todo->delete();

        return response()->json([
            'message' => 'Todo moved to trash',
        ]);
    }

    /**
     * Permanently delete the specified todo.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDestroy(int $id): JsonResponse
    {
        $todo = Todo::withTrashed()->findOrFail($id);

        // Check ownership
        if ($todo->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $todo->forceDelete();

        return response()->json([
            'message' => 'Todo permanently deleted',
        ]);
    }

    /**
     * Restore a soft-deleted todo.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $todo = Todo::withTrashed()->findOrFail($id);

        // Check ownership
        if ($todo->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $todo->restore();

        return response()->json([
            'message' => 'Todo restored successfully',
            'data' => new TodoResource($todo->fresh()->load('category')),
        ]);
    }

    /**
     * Mark todo as completed.
     *
     * @param Todo $todo
     * @return JsonResponse
     */
    public function complete(Todo $todo): JsonResponse
    {
        $todo->update([
            'is_completed' => true,
            'is_overdue' => false,
        ]);

        return response()->json([
            'message' => 'Todo marked as completed',
            'data' => new TodoResource($todo->fresh()->load('category')),
        ]);
    }

    /**
     * Mark todo as pending (uncomplete).
     *
     * @param Todo $todo
     * @return JsonResponse
     */
    public function uncomplete(Todo $todo): JsonResponse
    {
        $todo->update(['is_completed' => false]);
        $todo->checkOverdue();

        return response()->json([
            'message' => 'Todo marked as pending',
            'data' => new TodoResource($todo->fresh()->load('category')),
        ]);
    }

    /**
     * Get trashed todos.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trash(Request $request): JsonResponse
    {
        $todos = Todo::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->with(['category'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => TodoResource::collection($todos),
            'meta' => [
                'current_page' => $todos->currentPage(),
                'last_page' => $todos->lastPage(),
                'per_page' => $todos->perPage(),
                'total' => $todos->total(),
            ],
        ]);
    }

    /**
     * Export todos to CSV or JSON.
     *
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'json');
        
        $todos = Todo::where('user_id', $request->user()->id)
            ->with(['category'])
            ->get();

        if ($format === 'csv') {
            return $this->exportCsv($todos);
        }

        return response()->json([
            'data' => TodoResource::collection($todos),
            'exported_at' => now()->toISOString(),
            'total' => $todos->count(),
        ]);
    }

    /**
     * Export todos to CSV format.
     *
     * @param \Illuminate\Database\Eloquent\Collection $todos
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function exportCsv($todos)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="todos-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($todos) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'Title', 'Description', 'Priority', 'Due Date', 'Status', 'Category', 'Created At']);

            // Data
            foreach ($todos as $todo) {
                fputcsv($file, [
                    $todo->id,
                    $todo->title,
                    $todo->description,
                    $todo->priority,
                    $todo->due_date?->format('Y-m-d H:i:s'),
                    $todo->is_completed ? 'Completed' : 'Pending',
                    $todo->category?->name,
                    $todo->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
