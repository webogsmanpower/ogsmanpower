<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Seeker;
use App\Models\SeekerResume;
use Illuminate\Http\Request;

/**
 * AdminSeekerController
 * 
 * Handles seeker management for admins.
 * Includes search, view, and ban functionality.
 */
class AdminSeekerController extends Controller
{
    /**
     * List all seekers with optional filters.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'seeker')
            ->with(['seeker', 'seekerResume']);

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status (active/banned)
        if ($request->has('status')) {
            if ($request->status === 'banned') {
                $query->whereNotNull('banned_at');
            } elseif ($request->status === 'active') {
                $query->whereNull('banned_at');
            }
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $seekers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $seekers->items(),
            'meta' => [
                'current_page' => $seekers->currentPage(),
                'last_page' => $seekers->lastPage(),
                'per_page' => $seekers->perPage(),
                'total' => $seekers->total(),
            ],
        ]);
    }

    /**
     * Get seeker details.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::where('role', 'seeker')
            ->with(['seeker', 'seekerResume', 'currentSubscription.plan'])
            ->findOrFail($id);

        // Get application stats
        $applicationStats = [
            'total' => $user->seeker ? $user->seeker->applications()->count() : 0,
            'this_month' => $user->seeker ? $user->seeker->applications()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count() : 0,
            'pending' => $user->seeker ? $user->seeker->applications()->where('status', 'pending')->count() : 0,
            'accepted' => $user->seeker ? $user->seeker->applications()->where('status', 'accepted')->count() : 0,
        ];

        // Get recent activity logs if Spatie Activitylog is installed
        $activityLogs = [];
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            $activityLogs = \Spatie\Activitylog\Models\Activity::where('causer_id', $user->id)
                ->orWhere('subject_id', $user->id) // Assuming subject can be the user
                ->latest()
                ->take(10)
                ->get();
        }

        // Get available plans for upgrade dropdown
        $availablePlans = \App\Models\Plan::where('role_type', 'seeker')
            ->where('is_active', true)
            ->where('is_addon', false)
            ->get();

        $availableAddons = \App\Models\Plan::where('role_type', 'seeker')
            ->where('is_active', true)
            ->where('is_addon', true)
            ->get();

        return response()->json([
            'user' => $user,
            'application_stats' => $applicationStats,
            'activity_logs' => $activityLogs,
            'available_plans' => $availablePlans,
            'available_addons' => $availableAddons,
        ]);
    }

    /**
     * Ban a seeker.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function ban(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = User::where('role', 'seeker')->findOrFail($id);

        if ($user->banned_at) {
            return response()->json([
                'message' => 'Seeker is already banned.',
            ], 422);
        }

        $user->update([
            'banned_at' => now(),
            'ban_reason' => $request->reason,
            'banned_by' => $request->user()->id,
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Seeker banned successfully.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Unban a seeker.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unban($id)
    {
        $user = User::where('role', 'seeker')->findOrFail($id);

        if (!$user->banned_at) {
            return response()->json([
                'message' => 'Seeker is not banned.',
            ], 422);
        }

        $user->update([
            'banned_at' => null,
            'ban_reason' => null,
            'banned_by' => null,
        ]);

        return response()->json([
            'message' => 'Seeker unbanned successfully.',
            'user' => $user->fresh(),
        ]);
    }
}
