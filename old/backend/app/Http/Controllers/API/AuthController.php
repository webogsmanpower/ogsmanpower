<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use App\Http\Resources\UserResource;
use App\Http\Requests\API\LoginRequest;
use App\Http\Requests\API\RegisterRequest;
use App\Http\Requests\API\UpdatePasswordRequest;
use App\Mail\EmailVerificationCode;
use App\Models\EmailVerification;
use App\Models\LoginActivity;
use App\Models\Seeker;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * AuthController
 * 
 * Handles user authentication operations including registration, login,
 * email verification, password management, and session tracking.
 * 
 * @package App\Http\Controllers\API
 */

class AuthController extends Controller
{
    /**
     * Default pagination limit for list endpoints.
     */
    private const DEFAULT_PAGINATION_LIMIT = 20;
    
    /**
     * Maximum pagination limit to prevent excessive data retrieval.
     */
    private const MAX_PAGINATION_LIMIT = 100;

    /**
     * Register a new user account (as Seeker).
     * 
     * STRICT ROLE ENFORCEMENT:
     * - This endpoint creates SEEKER accounts only
     * - Email must be unique across all roles
     * - One email = One role
     * 
     * @param RegisterRequest $request Validated registration data
     * @return JsonResponse User data with authentication token
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = DB::transaction(function () use ($payload) {
            $fullName = $payload['name'] ?? trim(($payload['first_name'] ?? '') . ' ' . ($payload['last_name'] ?? ''));

            $user = User::create([
                'name' => $fullName,
                'email' => $payload['email'],
                'password' => Hash::make($payload['password']),
                'mobile' => $payload['mobile'] ?? $payload['phone_number'] ?? null,
                'role' => 'seeker', // Explicitly set role to seeker
            ]);

            $user->seeker()->create([
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'headline' => $payload['headline'] ?? null,
                'date_of_birth' => $payload['date_of_birth'] ?? null,
                'current_location' => $payload['current_location'] ?? null,
                'skills' => $payload['skills'] ?? null,
                'experience_years' => $payload['experience_years'] ?? 0,
                'is_profile_complete' => $payload['is_profile_complete'] ?? false,
                'bio' => $payload['bio'] ?? null,
                'resume_path' => $payload['resume_path'] ?? null,
            ]);

            return $user;
        });

        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;

        $verification = EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => random_int(100000, 999999),
            'expires_at' => now()->addMinutes(30),
        ]);

        Log::info('Seeker registration successful, sending verification email', [
            'user_id' => $user->id,
            'email' => $user->email,
            'verification_code' => $verification->code
        ]);

        // Queue email to prevent blocking
        dispatch(function () use ($user, $verification) {
            try {
                Mail::to($user->email)->send(new EmailVerificationCode($verification));
                Log::info('Verification email sent successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }
        });

        $this->recordLoginActivity($user, request(), 'success', $tokenResult->accessToken->id ?? null);

        return response()->json([
            'message' => 'Registration successful. Verification code sent.',
            'token' => $token,
            'user' => new \App\Http\Resources\UserResource($user->load('seeker')),
        ], 201);
    }

    /**
     * Authenticate user and create access token.
     * 
     * Uses eager loading for seeker relationship to prevent N+1 queries.
     * Records login activity for security tracking.
     * 
     * @param LoginRequest $request Validated login credentials
     * @return JsonResponse User data with authentication token
     * @throws ValidationException If credentials are invalid
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        // Eager load seeker and employer relationships to prevent N+1 query
        // Include resume for avatar fallback in UserResource
        $user = User::with(['seeker.resume', 'employer', 'employerMemberships', 'roles'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => ['email' => 'No account found with this email address.']
            ], 401);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => ['password' => 'The password you entered is incorrect.']
            ], 401);
        }

        // Fire login event to trigger timestamp tracking
        event(new Login('web', $user, false));

        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;

        $this->recordLoginActivity($user, $request, 'success', $tokenResult->accessToken->id ?? null);

        // User already has seeker loaded via eager loading
        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => new \App\Http\Resources\UserResource($user),
        ]);
    }

    /**
     * Update user password and invalidate all existing tokens.
     * 
     * Creates a new token after password change for continued access.
     * Clears user cache to ensure fresh data on next request.
     * 
     * @param UpdatePasswordRequest $request Validated password data
     * @return JsonResponse New authentication token
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->validated('new_password')),
        ]);

        $user->tokens()->delete();
        $newToken = $user->createToken('auth_token')->plainTextToken;
        
        // Clear any cached user data
        Cache::forget("user_{$user->id}");
        Cache::forget("user_sessions_{$user->id}_limit_" . self::DEFAULT_PAGINATION_LIMIT);

        return response()->json([
            'message' => 'Password updated successfully.',
            'token' => $newToken,
        ]);
    }

    /**
     * Get active sessions for the authenticated user.
     *
     * Returns active personal access tokens for the user.
     * Supports pagination via 'limit' and 'cursor' query parameters.
     * Results are cached for 1 minute to reduce database load.
     *
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Paginated list of active sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = min(
            (int) $request->query('limit', self::DEFAULT_PAGINATION_LIMIT),
            self::MAX_PAGINATION_LIMIT
        );

        $cacheKey = "user_sessions_{$userId}_limit_{$limit}";

        $tokens = Cache::remember($cacheKey, 60, function () use ($userId, $limit, $request) {
            return DB::table('personal_access_tokens')
                ->where('tokenable_id', $userId)
                ->where('tokenable_type', 'App\Models\User')
                ->whereNull('expires_at') // Only non-expired tokens
                ->orderByDesc('last_used_at')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function ($token) use ($request) {
                    // Get user agent and IP from login activity if available
                    $loginActivity = DB::table('login_activities')
                        ->where('token_id', $token->id)
                        ->latest('created_at')
                        ->first();

                    return [
                        'id' => $token->id,
                        'ip_address' => $loginActivity->ip_address ?? null,
                        'user_agent' => $loginActivity->user_agent ?? 'Unknown device',
                        'last_activity' => $token->last_used_at ? now()->setTimestamp(strtotime($token->last_used_at))->toIso8601String() : $token->created_at,
                        'is_current' => false, // We'll determine this differently if needed
                    ];
                });
        });

        return response()->json([
            'data' => $tokens,
            'meta' => [
                'limit' => $limit,
                'count' => $tokens->count(),
            ],
        ]);
    }

    /**
     * Get login history for the authenticated user.
     * 
     * Supports cursor-based pagination for efficient large dataset handling.
     * Uses 'cursor' (last seen ID) and 'limit' query parameters.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Paginated list of login activities
     */
    public function loginHistory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $limit = min(
            (int) $request->query('limit', self::DEFAULT_PAGINATION_LIMIT),
            self::MAX_PAGINATION_LIMIT
        );
        $cursor = $request->query('cursor'); // Last seen ID for cursor pagination
        
        $query = LoginActivity::query()
            ->where('user_id', $userId)
            ->latest('id');
        
        // Apply cursor-based pagination if cursor provided
        if ($cursor) {
            $query->where('id', '<', $cursor);
        }
        
        $activities = $query->limit($limit + 1)->get(); // Fetch one extra to check for more
        
        $hasMore = $activities->count() > $limit;
        if ($hasMore) {
            $activities = $activities->take($limit);
        }
        
        $history = $activities->map(function (LoginActivity $activity) {
            return [
                'id' => $activity->id,
                'status' => $activity->status,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
                'created_at' => $activity->created_at?->toIso8601String(),
            ];
        });
        
        $nextCursor = $hasMore && $activities->isNotEmpty() 
            ? $activities->last()->id 
            : null;

        return response()->json([
            'data' => $history,
            'meta' => [
                'limit' => $limit,
                'count' => $history->count(),
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
            ],
        ]);
    }

    /**
     * Record a login activity entry for security tracking.
     * 
     * Invalidates sessions cache after recording new activity.
     * 
     * @param User $user The user who logged in
     * @param Request|null $request The HTTP request (null for CLI contexts)
     * @param string $status Login status ('success' or 'failed')
     * @param int|null $tokenId The created token ID if successful
     * @return void
     */
    protected function recordLoginActivity(User $user, ?Request $request, string $status, ?int $tokenId = null): void
    {
        if (! $request) {
            return;
        }

        LoginActivity::create([
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
        ]);
        
        // Invalidate sessions cache after new login
        Cache::forget("user_sessions_{$user->id}_limit_" . self::DEFAULT_PAGINATION_LIMIT);
    }

    /**
     * Verify user email with verification code.
     * 
     * Validates the code against stored verification record.
     * Marks user email as verified and deletes the verification record.
     * 
     * @param Request $request Request containing email and verification code
     * @return JsonResponse Success or error message
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $verification = EmailVerification::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        // Mark user email as verified
        $user = $verification->user;
        $user->email_verified_at = now();
        $user->save();

        // Delete the verification record
        $verification->delete();

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    /**
     * Logout user and revoke current token.
     * 
     * Deletes the current access token and clears any cached user data.
     * Returns success response even if token doesn't exist for graceful handling.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Success message
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user) {
            // Delete the current access token
            $user->currentAccessToken()?->delete();
            
            // Clear any cached user data
            Cache::forget("user_{$user->id}");
            Cache::forget("user_sessions_{$user->id}_limit_" . self::DEFAULT_PAGINATION_LIMIT);
            
            // Clear resume cache to ensure fresh data on next login
            Cache::forget("resume_user_{$user->id}");
            Cache::forget("resume_full_{$user->id}");
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Resend email verification code.
     * 
     * Deletes any existing verification codes before creating a new one.
     * New code expires in 30 minutes.
     * 
     * @param Request $request Request containing user email
     * @return JsonResponse Success or error message
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified.',
            ], 422);
        }

        // Delete any existing verification codes
        EmailVerification::where('user_id', $user->id)->delete();

        // Create new verification code
        $verification = EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => random_int(100000, 999999),
            'expires_at' => now()->addMinutes(30),
        ]);

        // Queue email to prevent blocking
        dispatch(function () use ($user, $verification) {
            Mail::to($user->email)->send(new EmailVerificationCode($verification));
        });

        return response()->json([
            'message' => 'Verification code sent successfully.',
        ]);
    }
}
