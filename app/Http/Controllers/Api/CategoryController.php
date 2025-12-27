<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where('user_id', $request->user()->id)
            ->withCount(['todos', 'todos as completed_todos_count' => function ($query) {
                $query->where('is_completed', true);
            }, 'todos as pending_todos_count' => function ($query) {
                $query->where('is_completed', false);
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'total' => $categories->count(),
            ],
        ]);
    }

    /**
     * Store a newly created category.
     *
     * @param StoreCategoryRequest $request
     * @return JsonResponse
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'color' => $request->input('color', '#3B82F6'),
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified category.
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function show(Category $category): JsonResponse
    {
        // Check ownership
        if ($category->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $category->loadCount(['todos', 'todos as completed_todos_count' => function ($query) {
            $query->where('is_completed', true);
        }, 'todos as pending_todos_count' => function ($query) {
            $query->where('is_completed', false);
        }]);

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update the specified category.
     *
     * @param UpdateCategoryRequest $request
     * @param Category $category
     * @return JsonResponse
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        // Check ownership
        if ($category->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $category->update($request->validated());

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category->fresh()),
        ]);
    }

    /**
     * Remove the specified category.
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check ownership
        if ($category->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Set category_id to null for all todos in this category
        $category->todos()->update(['category_id' => null]);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}
