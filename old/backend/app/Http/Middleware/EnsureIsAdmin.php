<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureIsAdmin Middleware
 * 
 * Ensures the authenticated user has the 'admin' role.
 * Protects all /admin/* routes from unauthorized access.
 */
class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            return redirect('/admin/login');
        }

        // Check if user has any admin role using Spatie permissions
        if (!$user->hasAnyRole(['super_admin', 'verification_officer', 'support_agent', 'content_manager', 'finance_manager', 'job_moderator'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. This resource is only available to administrators.',
                    'error_code' => 'ADMIN_ACCESS_REQUIRED',
                    'required_roles' => ['super_admin', 'verification_officer', 'support_agent', 'content_manager', 'finance_manager', 'job_moderator'],
                    'current_roles' => $user->roles->pluck('name')->toArray() ?? [],
                ], 403);
            }
            return redirect('/');
        }

        return $next($request);
    }
}
