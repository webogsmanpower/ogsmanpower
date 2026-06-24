<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AdminAuthController
 * 
 * Handles admin authentication separately from regular user auth.
 * Admins have a distinct login flow for security.
 */
class AdminAuthController extends Controller
{
    /**
     * Admin login.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // 1. Log the incoming request
        Log::info('Admin Login Attempt:', $request->all());

        // 2. Validate manually to catch the error
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            Log::error('Validation Failed:', $validator->errors()->toArray());
            return response()->json(['message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        // 3. Attempt Auth
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 4. Role Check
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            Auth::logout();
            return response()->json(['message' => 'User is not an admin'], 403);
        }

        // 5. Success
        $token = $user->createToken('admin-token')->plainTextToken;
        return response()->json(['token' => $token, 'role' => 'super_admin', 'user' => $user]);
    }

    /**
     * Admin logout.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Admin logged out successfully',
        ]);
    }

    /**
     * Get current admin user.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'super_admin' => $user->super_admin ?? false,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ]);
    }
}
