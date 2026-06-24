<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckEmployerVerification Middleware
 * 
 * Blocks unverified employers from accessing protected resources
 * like "Post Job" and "Search Candidates" APIs.
 * 
 * This middleware should be applied AFTER EnsureIsEmployer middleware.
 */
class CheckEmployerVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // User and employer existence should already be checked by EnsureIsEmployer
        if (!$user || !$user->employer) {
            return response()->json([
                'message' => 'Employer profile not found.',
                'error_code' => 'EMPLOYER_NOT_FOUND',
            ], 403);
        }

        $employer = $user->employer;

        // Check verification status
        if (!$employer->is_verified || $employer->verification_status !== 'verified') {
            $statusMessages = [
                'pending' => 'Your employer account is pending verification. Please wait for admin approval before accessing this feature.',
                'rejected' => 'Your employer account verification was rejected. Please contact support or resubmit your documents.',
            ];

            $status = $employer->verification_status ?? 'pending';
            
            return response()->json([
                'message' => $statusMessages[$status] ?? 'Your employer account is not verified.',
                'error_code' => 'EMPLOYER_NOT_VERIFIED',
                'verification_status' => $status,
                'rejection_reason' => $status === 'rejected' ? $employer->rejection_reason : null,
                'redirect' => '/employer/verification-pending',
            ], 403);
        }

        return $next($request);
    }
}
