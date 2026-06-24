<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureIsEmployer Middleware
 * 
 * Ensures the authenticated user has the 'employer' role.
 * Prevents seekers from accessing employer routes.
 */
class EnsureIsEmployer
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

        // Check if user has employer role
        if ($user->role !== 'employer') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. This resource is only available to employers.',
                    'error_code' => 'ROLE_MISMATCH',
                    'required_role' => 'employer',
                    'current_role' => $user->role ?? 'seeker',
                ], 403);
            }
            return redirect('/seeker/dashboard');
        }

        // Also verify user has an employer profile
        if (!$user->employer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Employer profile not found. Please complete employer registration.',
                    'error_code' => 'EMPLOYER_PROFILE_MISSING',
                    'redirect' => '/employer/register',
                ], 403);
            }
            return redirect('/employer/register');
        }

        return $next($request);
    }
}
