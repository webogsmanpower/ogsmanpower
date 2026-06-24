<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccess
{
    /**
     * Handle an incoming request with permission-based access control.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The required permission to access the route
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has the required permission
        if (!$user->can($permission)) {
            return response()->json([
                'message' => 'Unauthorized - Insufficient permissions',
                'required_permission' => $permission,
                'user_permissions' => $user->getAllPermissions()->pluck('name'),
            ], 403);
        }

        return $next($request);
    }
}
