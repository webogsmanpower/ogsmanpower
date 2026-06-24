<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserSubscriptionController extends Controller
{
    /**
     * Update or create a user's subscription.
     * 
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $userId)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = User::findOrFail($userId);
        $plan = Plan::findOrFail($request->plan_id);

        // Verify role match
        if ($user->role !== $plan->role_type && !($user->hasRole($plan->role_type))) {
             // Basic check - might need refinement for complex role setups
             if ($user->role === 'employer' && $plan->role_type !== 'employer') {
                 return response()->json(['message' => 'Plan role mismatch'], 422);
             }
             if ($user->role === 'seeker' && $plan->role_type !== 'seeker') {
                 return response()->json(['message' => 'Plan role mismatch'], 422);
             }
        }

        DB::transaction(function () use ($user, $plan) {
            // Cancel existing active subscription
            $user->currentSubscription()->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Calculate expiration
            $expiresAt = null;
            if ($plan->interval === 'monthly') {
                $expiresAt = now()->addMonth();
            } elseif ($plan->interval === 'yearly') {
                $expiresAt = now()->addYear();
            }
            // lifetime/one_time stays null

            // Create new subscription
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'payment_method' => 'admin_override',
            ]);
        });

        return response()->json([
            'message' => 'Subscription updated successfully',
            'user' => $user->load('currentSubscription.plan') // Return updated user data structure
        ]);
    }

    /**
     * Cancel a user's subscription.
     * 
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($userId)
    {
        $user = User::findOrFail($userId);
        $subscription = $user->currentSubscription;

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'user' => $user->load('currentSubscription.plan')
        ]);
    }
}
