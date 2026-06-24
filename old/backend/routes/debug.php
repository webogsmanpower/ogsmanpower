<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Seeker;

Route::get('/debug/user-images', function () {
    $user = auth()->user();
    
    if (!$user) {
        return response()->json(['error' => 'Not authenticated'], 401);
    }
    
    // Load seeker relationship
    $user->load('seeker');
    
    return response()->json([
        'user_id' => $user->id,
        'user' => [
            'profile_image_url' => $user->profile_image_url,
            'profile_photo_path' => $user->profile_photo_path,
            'avatar' => $user->avatar,
        ],
        'seeker' => $user->seeker ? [
            'profile_image_url' => $user->seeker->profile_image_url,
            'profile_image_path' => $user->seeker->profile_image_path,
            'avatar_url' => $user->seeker->avatar_url,
        ] : null,
        'all_user_fields' => $user->toArray(),
        'all_seeker_fields' => $user->seeker ? $user->seeker->toArray() : null,
    ]);
});

Route::get('/debug/user-resource', function () {
    $user = auth()->user();
    
    if (!$user) {
        return response()->json(['error' => 'Not authenticated'], 401);
    }
    
    // Load relationships like the real API
    $user->load(['seeker', 'seekerResume', 'employer', 'employerMemberships']);
    
    // Return the actual UserResource
    return new \App\Http\Resources\UserResource($user);
});
