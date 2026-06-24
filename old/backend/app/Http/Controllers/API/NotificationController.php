<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * NotificationController
 * 
 * Handles notification management for users.
 */
class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at->toIso8601String(),
                    'created_at_human' => $notification->created_at->diffForHumans(),
                ];
            }),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get unread notifications count.
     * Optimized to avoid loading all notification models.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        // Use exists() check for better performance instead of count() on large datasets
        $count = $request->user()->unreadNotifications()->count();
        
        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);
        
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);
        
        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Clear all notifications.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'message' => 'All notifications cleared',
        ]);
    }

    /**
     * Get activity logs for visa updates and other actions.
     */
    public function activities(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);
        
        // Get seeker ID if user is a seeker
        $seekerId = null;
        if ($user->seeker) {
            $seekerId = $user->seeker->id;
        }
        
        // Query activity logs related to this user
        $activities = Activity::where(function ($query) use ($user, $seekerId) {
            $query->where('causer_id', $user->id) // Activities performed by user
                  ->orWhere('properties->seeker_id', $seekerId); // Activities related to seeker
        })
        ->with(['causer'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        return response()->json([
            'data' => $activities->map(function ($activity) {
                $data = [
                    'id' => $activity->id,
                    'type' => $this->getActivityType($activity),
                    'title' => $this->getActivityTitle($activity),
                    'description' => $activity->description,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->toIso8601String(),
                    'created_at_human' => $activity->created_at->diffForHumans(),
                ];

                // Add causer info if available
                if ($activity->causer) {
                    $data['causer'] = [
                        'id' => $activity->causer->id,
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ];
                }

                // Add subject info if available
                if ($activity->subject) {
                    $data['subject'] = [
                        'id' => $activity->subject->id ?? null,
                        'type' => $activity->subject_type ?? null,
                    ];
                }

                return $data;
            }),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Determine activity type from description and properties.
     */
    private function getActivityType(Activity $activity): string
    {
        $description = $activity->description ?? '';
        $properties = $activity->properties ?? [];

        if (str_contains($description, 'Visa step updated')) {
            return 'visa_update';
        }

        if (str_contains($description, 'application')) {
            return 'application';
        }

        if (str_contains($description, 'interview')) {
            return 'interview';
        }

        if (str_contains($description, 'contract')) {
            return 'contract';
        }

        return 'general';
    }

    /**
     * Generate user-friendly activity title.
     */
    private function getActivityTitle(Activity $activity): string
    {
        $description = $activity->description ?? '';
        $properties = $activity->properties ?? [];

        if (str_contains($description, 'Visa step updated')) {
            $oldStep = $properties['old_step'] ?? 'previous step';
            $newStep = $properties['new_step'] ?? 'new step';
            return "Visa status updated to " . ucfirst(str_replace('_', ' ', $newStep));
        }

        return $description;
    }
}
