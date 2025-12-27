<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TodoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
        Route::get('/user', [AuthController::class, 'user'])->name('api.auth.user');
    });

    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('api.profile.show');
        Route::put('/', [ProfileController::class, 'update'])->name('api.profile.update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('api.profile.password');
        Route::put('/settings', [ProfileController::class, 'updateSettings'])->name('api.profile.settings');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('api.profile.destroy');
    });

    // Todos Management
    Route::prefix('todos')->group(function () {
        Route::get('/', [TodoController::class, 'index'])->name('api.todos.index');
        Route::post('/', [TodoController::class, 'store'])->name('api.todos.store');
        Route::get('/trash', [TodoController::class, 'trash'])->name('api.todos.trash');
        Route::get('/export', [TodoController::class, 'export'])->name('api.todos.export');
        Route::get('/{todo}', [TodoController::class, 'show'])->name('api.todos.show');
        Route::put('/{todo}', [TodoController::class, 'update'])->name('api.todos.update');
        Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('api.todos.destroy');
        Route::delete('/{id}/force', [TodoController::class, 'forceDestroy'])->name('api.todos.force-destroy');
        Route::patch('/{id}/restore', [TodoController::class, 'restore'])->name('api.todos.restore');
        Route::patch('/{todo}/complete', [TodoController::class, 'complete'])->name('api.todos.complete');
        Route::patch('/{todo}/uncomplete', [TodoController::class, 'uncomplete'])->name('api.todos.uncomplete');
    });

    // Categories Management
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('api.categories.index');
        Route::post('/', [CategoryController::class, 'store'])->name('api.categories.store');
        Route::get('/{category}', [CategoryController::class, 'show'])->name('api.categories.show');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');
    });

    // Statistics & Dashboard
    Route::prefix('stats')->group(function () {
        Route::get('/', [StatsController::class, 'index'])->name('api.stats.index');
        Route::get('/summary', [StatsController::class, 'summary'])->name('api.stats.summary');
        Route::get('/priority-breakdown', [StatsController::class, 'priorityBreakdown'])->name('api.stats.priority');
        Route::get('/category-breakdown', [StatsController::class, 'categoryBreakdown'])->name('api.stats.category');
        Route::get('/activity-timeline', [StatsController::class, 'activityTimeline'])->name('api.stats.activity');
        Route::get('/overdue-analysis', [StatsController::class, 'overdueAnalysis'])->name('api.stats.overdue');
    });
});
