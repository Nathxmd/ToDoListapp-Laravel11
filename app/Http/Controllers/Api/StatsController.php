<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get comprehensive dashboard statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'summary' => $this->getSummaryStats($userId),
            'priority_breakdown' => $this->getPriorityBreakdown($userId),
            'category_breakdown' => $this->getCategoryBreakdown($userId),
            'completion_rate' => $this->getCompletionRate($userId),
            'overdue_analysis' => $this->getOverdueAnalysis($userId),
            'recent_activity' => $this->getRecentActivity($userId),
        ]);
    }

    /**
     * Get summary statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'data' => $this->getSummaryStats($userId),
        ]);
    }

    /**
     * Get priority breakdown.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function priorityBreakdown(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'data' => $this->getPriorityBreakdown($userId),
        ]);
    }

    /**
     * Get category breakdown.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function categoryBreakdown(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'data' => $this->getCategoryBreakdown($userId),
        ]);
    }

    /**
     * Get activity timeline.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activityTimeline(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $days = $request->input('days', 7);

        return response()->json([
            'data' => $this->getActivityTimeline($userId, $days),
        ]);
    }

    /**
     * Get overdue todos analysis.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overdueAnalysis(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'data' => $this->getOverdueAnalysis($userId),
        ]);
    }

    /**
     * Get summary statistics data.
     *
     * @param int $userId
     * @return array
     */
    private function getSummaryStats(int $userId): array
    {
        $total = Todo::where('user_id', $userId)->count();
        $completed = Todo::where('user_id', $userId)->where('is_completed', true)->count();
        $pending = Todo::where('user_id', $userId)->where('is_completed', false)->count();
        $overdue = Todo::where('user_id', $userId)->where('is_overdue', true)->count();
        $dueToday = Todo::where('user_id', $userId)
            ->whereDate('due_date', today())
            ->where('is_completed', false)
            ->count();
        $dueThisWeek = Todo::where('user_id', $userId)
            ->whereBetween('due_date', [now(), now()->endOfWeek()])
            ->where('is_completed', false)
            ->count();

        return [
            'total_todos' => $total,
            'completed_todos' => $completed,
            'pending_todos' => $pending,
            'overdue_todos' => $overdue,
            'due_today' => $dueToday,
            'due_this_week' => $dueThisWeek,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get priority breakdown data.
     *
     * @param int $userId
     * @return array
     */
    private function getPriorityBreakdown(int $userId): array
    {
        $breakdown = Todo::where('user_id', $userId)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->priority => $item->count];
            });

        return [
            'high' => $breakdown->get('high', 0),
            'medium' => $breakdown->get('medium', 0),
            'low' => $breakdown->get('low', 0),
            'total' => $breakdown->sum(),
        ];
    }

    /**
     * Get category breakdown data.
     *
     * @param int $userId
     * @return array
     */
    private function getCategoryBreakdown(int $userId): array
    {
        $categories = Category::where('user_id', $userId)
            ->withCount([
                'todos',
                'todos as completed_count' => function ($query) {
                    $query->where('is_completed', true);
                },
                'todos as pending_count' => function ($query) {
                    $query->where('is_completed', false);
                },
            ])
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'total_todos' => $category->todos_count,
                    'completed_todos' => $category->completed_count,
                    'pending_todos' => $category->pending_count,
                    'completion_percentage' => $category->todos_count > 0 
                        ? round(($category->completed_count / $category->todos_count) * 100, 2) 
                        : 0,
                ];
            });

        $uncategorized = Todo::where('user_id', $userId)
            ->whereNull('category_id')
            ->count();

        $uncategorizedCompleted = Todo::where('user_id', $userId)
            ->whereNull('category_id')
            ->where('is_completed', true)
            ->count();

        return [
            'categories' => $categories,
            'uncategorized' => [
                'total_todos' => $uncategorized,
                'completed_todos' => $uncategorizedCompleted,
                'pending_todos' => $uncategorized - $uncategorizedCompleted,
                'completion_percentage' => $uncategorized > 0 
                    ? round(($uncategorizedCompleted / $uncategorized) * 100, 2) 
                    : 0,
            ],
        ];
    }

    /**
     * Get completion rate data.
     *
     * @param int $userId
     * @return array
     */
    private function getCompletionRate(int $userId): array
    {
        $last7Days = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $completed = Todo::where('user_id', $userId)
                ->where('is_completed', true)
                ->whereDate('updated_at', $date)
                ->count();
            
            $created = Todo::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->count();

            $last7Days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'completed' => $completed,
                'created' => $created,
            ];
        }

        return [
            'last_7_days' => $last7Days,
        ];
    }

    /**
     * Get overdue analysis data.
     *
     * @param int $userId
     * @return array
     */
    private function getOverdueAnalysis(int $userId): array
    {
        $overdueTodos = Todo::where('user_id', $userId)
            ->where('is_overdue', true)
            ->where('is_completed', false)
            ->with('category')
            ->get();

        $byPriority = $overdueTodos->groupBy('priority')->map(function ($todos) {
            return $todos->count();
        });

        $byCategory = $overdueTodos->groupBy('category.name')->map(function ($todos) {
            return $todos->count();
        });

        return [
            'total_overdue' => $overdueTodos->count(),
            'by_priority' => [
                'high' => $byPriority->get('high', 0),
                'medium' => $byPriority->get('medium', 0),
                'low' => $byPriority->get('low', 0),
            ],
            'by_category' => $byCategory,
            'oldest_overdue' => $overdueTodos->sortBy('due_date')->first()?->only(['id', 'title', 'due_date', 'priority']),
        ];
    }

    /**
     * Get recent activity data.
     *
     * @param int $userId
     * @return array
     */
    private function getRecentActivity(int $userId): array
    {
        $recentlyCompleted = Todo::where('user_id', $userId)
            ->where('is_completed', true)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'updated_at', 'priority']);

        $recentlyCreated = Todo::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'created_at', 'priority']);

        return [
            'recently_completed' => $recentlyCompleted,
            'recently_created' => $recentlyCreated,
        ];
    }

    /**
     * Get activity timeline data.
     *
     * @param int $userId
     * @param int $days
     * @return array
     */
    private function getActivityTimeline(int $userId, int $days = 7): array
    {
        $timeline = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $created = Todo::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->count();
            
            $completed = Todo::where('user_id', $userId)
                ->where('is_completed', true)
                ->whereDate('updated_at', $date)
                ->count();
            
            $deleted = Todo::onlyTrashed()
                ->where('user_id', $userId)
                ->whereDate('deleted_at', $date)
                ->count();

            $timeline[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('l'),
                'created' => $created,
                'completed' => $completed,
                'deleted' => $deleted,
            ];
        }

        return $timeline;
    }
}
