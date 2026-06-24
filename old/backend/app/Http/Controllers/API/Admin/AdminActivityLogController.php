<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class AdminActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        // Filter by log name (module)
        if ($request->has('log_name') && $request->log_name !== 'all') {
            $query->where('log_name', $request->log_name);
        }

        // Filter by event type
        if ($request->has('event') && $request->event !== 'all') {
            $query->where('event', $request->event);
        }

        // Filter by causer (admin user)
        if ($request->has('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // Filter by subject type
        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search in description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereJsonContains('properties->attributes', $search);
            });
        }

        $activities = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activity log statistics.
     */
    public function stats(): JsonResponse
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        $stats = [
            'today' => Activity::whereDate('created_at', '>=', $today)->count(),
            'this_week' => Activity::whereDate('created_at', '>=', $thisWeek)->count(),
            'this_month' => Activity::whereDate('created_at', '>=', $thisMonth)->count(),
            'total' => Activity::count(),
        ];

        // Activity by log name (module)
        $byModule = Activity::selectRaw('log_name, COUNT(*) as count')
            ->groupBy('log_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->pluck('count', 'log_name');

        // Activity by event type
        $byEvent = Activity::selectRaw('event, COUNT(*) as count')
            ->whereNotNull('event')
            ->groupBy('event')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'event');

        // Recent activity by day (last 7 days)
        $byDay = Activity::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => $stats,
                'by_module' => $byModule,
                'by_event' => $byEvent,
                'by_day' => $byDay,
            ],
        ]);
    }

    /**
     * Get available log names (modules).
     */
    public function logNames(): JsonResponse
    {
        $logNames = Activity::select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return response()->json([
            'success' => true,
            'data' => $logNames,
        ]);
    }

    /**
     * Get available event types.
     */
    public function eventTypes(): JsonResponse
    {
        $events = Activity::select('event')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Get a single activity log entry.
     */
    public function show(int $id): JsonResponse
    {
        $activity = Activity::with(['causer', 'subject'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Get activity for a specific subject.
     */
    public function forSubject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_type' => 'required|string',
            'subject_id' => 'required|integer',
        ]);

        $activities = Activity::where('subject_type', $validated['subject_type'])
            ->where('subject_id', $validated['subject_id'])
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get activity caused by a specific user.
     */
    public function forCauser(int $userId): JsonResponse
    {
        $activities = Activity::where('causer_id', $userId)
            ->where('causer_type', 'App\\Models\\User')
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Export activity logs.
     */
    public function export(Request $request): JsonResponse
    {
        $query = Activity::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->has('log_name') && $request->log_name !== 'all') {
            $query->where('log_name', $request->log_name);
        }
        if ($request->has('event') && $request->event !== 'all') {
            $query->where('event', $request->event);
        }
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $activities = $query->limit(1000)->get();

        // Format for export
        $exportData = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'event' => $activity->event,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer_name' => $activity->causer?->name ?? 'System',
                'causer_email' => $activity->causer?->email ?? '',
                'properties' => json_encode($activity->properties),
                'created_at' => $activity->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'count' => $exportData->count(),
        ]);
    }
}
