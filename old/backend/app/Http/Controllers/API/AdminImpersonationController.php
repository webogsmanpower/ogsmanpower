<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AdminImpersonationController
 * 
 * Shadow Login (Impersonation) functionality for Admin Module.
 * Allows admins to log in as any user for debugging purposes.
 * 
 * Security:
 * - Only admins can impersonate
 * - Cannot impersonate other admins
 * - All impersonation actions are logged
 * - Original admin token is preserved for returning
 */
class AdminImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     * 
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function impersonate(Request $request, $userId)
    {
        $admin = $request->user();
        
        // Verify admin role
        if ($admin->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only admins can impersonate users.',
            ], 403);
        }

        // Find target user
        $targetUser = User::find($userId);
        
        if (!$targetUser) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Cannot impersonate other admins
        if ($targetUser->role === 'admin') {
            return response()->json([
                'message' => 'Cannot impersonate admin users.',
            ], 403);
        }

        // Log the impersonation
        Log::info('Admin Impersonation Started', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'target_user_id' => $targetUser->id,
            'target_user_email' => $targetUser->email,
            'target_user_role' => $targetUser->role,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Create a new token for the target user
        $impersonationToken = $targetUser->createToken('impersonation-token', ['impersonated'])->plainTextToken;

        // Store original admin info in session/cache for returning
        $returnData = [
            'admin_id' => $admin->id,
            'admin_token' => $request->bearerToken(),
            'started_at' => now()->toIso8601String(),
        ];

        return response()->json([
            'message' => 'Impersonation started successfully.',
            'token' => $impersonationToken,
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
            ],
            'return_data' => base64_encode(json_encode($returnData)),
            'redirect_to' => $this->getRedirectPath($targetUser->role),
        ]);
    }

    /**
     * Stop impersonating and return to admin account.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopImpersonation(Request $request)
    {
        $returnDataEncoded = $request->input('return_data');
        
        if (!$returnDataEncoded) {
            return response()->json([
                'message' => 'No impersonation session found.',
            ], 400);
        }

        try {
            $returnData = json_decode(base64_decode($returnDataEncoded), true);
            
            if (!$returnData || !isset($returnData['admin_id'])) {
                throw new \Exception('Invalid return data');
            }

            $admin = User::find($returnData['admin_id']);
            
            if (!$admin || $admin->role !== 'admin') {
                return response()->json([
                    'message' => 'Invalid admin session.',
                ], 400);
            }

            // Revoke the impersonation token
            $request->user()->currentAccessToken()->delete();

            // Log the end of impersonation
            Log::info('Admin Impersonation Ended', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'impersonated_user_id' => $request->user()->id,
                'duration' => now()->diffForHumans($returnData['started_at']),
            ]);

            return response()->json([
                'message' => 'Impersonation ended successfully.',
                'admin_token' => $returnData['admin_token'],
                'redirect_to' => '/admin/dashboard',
            ]);

        } catch (\Exception $e) {
            Log::error('Impersonation stop failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Failed to end impersonation. Please log out manually.',
            ], 500);
        }
    }

    /**
     * Get impersonation history (audit log).
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        // This would typically query an audit log table
        // For now, return a placeholder
        return response()->json([
            'message' => 'Impersonation history is stored in application logs.',
            'note' => 'Check storage/logs/laravel.log for impersonation records.',
        ]);
    }

    /**
     * Get redirect path based on user role.
     */
    private function getRedirectPath(string $role): string
    {
        return match ($role) {
            'seeker' => '/seeker/dashboard',
            'employer' => '/employer/dashboard',
            default => '/',
        };
    }
}
