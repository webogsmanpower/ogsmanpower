<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileImageRequest;
use App\Services\ImageUploadService;
use App\Models\User;
use App\Models\Seeker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

/**
 * ProfileImageController
 * 
 * Handles profile image upload, update, and deletion for users.
 * Supports both User and Seeker profile images.
 * 
 * @package App\Http\Controllers\API
 */
class ProfileImageController extends Controller
{
    protected ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Upload or update profile image
     * 
     * @param ProfileImageRequest $request
     * @return JsonResponse
     */
    public function upload(ProfileImageRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not authenticated'
            ], 401);
        }

        try {
            $file = $request->file('profile_image');
            
            // Upload image using service
            $imagePath = $this->imageService->uploadProfileImage($file, $user);
            
            // Update user/seeker record
            if ($user->seeker) {
                // Update seeker profile image
                $user->seeker->update([
                    'profile_image_path' => $imagePath,
                    'profile_image_url' => Storage::url($imagePath)
                ]);
                
                // Also update user record for backwards compatibility
                $user->update([
                    'profile_photo_path' => $imagePath,
                    'profile_image_url' => Storage::url($imagePath)
                ]);
                
                return response()->json([
                    'message' => 'Profile image uploaded successfully',
                    'profile_image_url' => Storage::url($imagePath),
                    'profile_image_path' => $imagePath,
                    'user_type' => 'seeker'
                ]);
            } else {
                // Update user profile image (for employers or admin users)
                $user->update([
                    'profile_photo_path' => $imagePath,
                    'profile_image_url' => Storage::url($imagePath)
                ]);
                
                return response()->json([
                    'message' => 'Profile image uploaded successfully',
                    'profile_image_url' => Storage::url($imagePath),
                    'profile_image_path' => $imagePath,
                    'user_type' => 'user'
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload profile image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove profile image
     * 
     * @return JsonResponse
     */
    public function remove(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not authenticated'
            ], 401);
        }

        try {
            // Get current image path
            $currentPath = null;
            
            if ($user->seeker) {
                $currentPath = $user->seeker->profile_image_path;
                
                // Clear seeker profile image
                $user->seeker->update([
                    'profile_image_path' => null,
                    'profile_image_url' => null
                ]);
            }
            
            // Also clear user record
            $user->update([
                'profile_photo_path' => null,
                'profile_image_url' => null
            ]);
            
            // Delete old image file if it exists
            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }
            
            return response()->json([
                'message' => 'Profile image removed successfully',
                'profile_image_url' => null,
                'profile_image_path' => null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove profile image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current profile image info
     * 
     * @return JsonResponse
     */
    public function getCurrent(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'error' => 'User not authenticated'
            ], 401);
        }

        $imageUrl = null;
        $imagePath = null;
        
        if ($user->seeker) {
            $imageUrl = $user->seeker->profile_image_url;
            $imagePath = $user->seeker->profile_image_path;
        } else {
            $imageUrl = $user->profile_image_url;
            $imagePath = $user->profile_photo_path;
        }
        
        return response()->json([
            'profile_image_url' => $imageUrl,
            'profile_image_path' => $imagePath,
            'has_profile_image' => !empty($imageUrl)
        ]);
    }
}
