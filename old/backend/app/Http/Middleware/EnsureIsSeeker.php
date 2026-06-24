<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureIsSeeker Middleware
 * 
 * Ensures the authenticated user has the 'seeker' role.
 * Prevents employers from accessing seeker routes.
 */
class EnsureIsSeeker
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
            return redirect('/auth/signin');
        }

        // Check if user has seeker role
        if ($user->role !== 'seeker') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. This resource is only available to job seekers.',
                    'error_code' => 'ROLE_MISMATCH',
                    'required_role' => 'seeker',
                    'current_role' => $user->role ?? 'unknown',
                ], 403);
            }
            return redirect('/employer/dashboard');
        }

        return $next($request);
    }
}
