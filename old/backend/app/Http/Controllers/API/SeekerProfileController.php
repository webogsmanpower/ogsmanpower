<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeekerResource;
use App\Http\Resources\UserResource;
use App\Services\JobApplicationService;
use App\Services\SeekerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * SeekerProfileController
 * 
 * Handles seeker profile operations using the SeekerProfileService.
 * Controllers only handle HTTP concerns - all business logic is in the service.
 * 
 * @package App\Http\Controllers\API
 */
class SeekerProfileController extends Controller
{
    public function __construct(
        protected SeekerProfileService $profileService,
        protected JobApplicationService $applicationService
    ) {}

    /**
     * Get the authenticated seeker's profile.
     * 
     * Returns standardized profile data via SeekerResource.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;

        if (!$seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        // Load relationships for complete data
        $seeker->load(['user', 'resume']);

        return response()->json([
            'data' => new SeekerResource($seeker),
        ]);
    }

    /**
     * Update the authenticated seeker's profile.
     * 
     * Handles profile data updates and optional image uploads.
     * Profile completion is automatically recalculated.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'profession' => 'sometimes|string|max:100',
            'headline' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:2000',
            'date_of_birth' => 'sometimes|date|before:today',
            'current_location' => 'sometimes|string|max:255',
            'experience_years' => 'sometimes|integer|min:0|max:50',
            'skills' => 'sometimes|array',
            'skills.*' => 'string|max:100',
            'mobile' => 'sometimes|string|max:20',
            'phone' => 'sometimes|string|max:20',
            
            // Driver fields
            'license_number' => 'sometimes|string|max:50',
            'license_expiry_date' => 'sometimes|date',
            'license_issuing_country' => 'sometimes|string|max:100',
            'license_issuing_authority' => 'sometimes|string|max:100',
            'license_type' => 'sometimes|string|max:50',
            'accident_free_years' => 'sometimes|integer|min:0',
            'has_clean_driving_record' => 'sometimes|boolean',
            
            // Domestic worker fields
            'number_of_children' => 'sometimes|integer|min:0',
            'skill_washing' => 'sometimes|boolean',
            'skill_cooking' => 'sometimes|boolean',
            'skill_babysitting' => 'sometimes|boolean',
            'skill_cleaning' => 'sometimes|boolean',
            
            // Image uploads
            'profile_image' => 'sometimes|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'full_body_image' => 'sometimes|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $profileImage = $request->file('profile_image');
        $fullBodyImage = $request->file('full_body_image');

        // Remove file fields from data array (handled separately)
        unset($data['profile_image'], $data['full_body_image']);

        $seeker = $this->profileService->updateProfile(
            $user,
            $data,
            $profileImage,
            $fullBodyImage
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new SeekerResource($seeker),
        ]);
    }

    /**
     * Get profile completion details.
     * 
     * Returns current completion percentage and missing fields.
     */
    public function completion(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;

        if (!$seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        $seeker->load('user');
        $completion = $this->profileService->calculateCompletion($seeker);

        return response()->json([
            'data' => [
                'completion' => $completion,
                'is_complete' => $completion >= 80,
                'avatar_url' => $this->profileService->resolveAvatarUrl($seeker),
                'display_name' => $this->profileService->resolveFullName($seeker),
            ],
        ]);
    }

    /**
     * Get seeker dashboard data.
     * 
     * Aggregates profile, applications, and activity data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;

        if (!$seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        $seeker->load(['user', 'resume']);

        // Get application stats
        $applicationStats = $this->applicationService->getSeekerStats($seeker);

        // Get recent activity
        $recentActivity = $this->applicationService->getActivityFeed($seeker, 10);

        return response()->json([
            'data' => [
                'profile' => [
                    'id' => (int) $seeker->id,
                    'display_name' => $this->profileService->resolveFullName($seeker),
                    'avatar_url' => $this->profileService->resolveAvatarUrl($seeker),
                    'headline' => (string) ($seeker->headline ?? ''),
                    'profession' => (string) ($seeker->profession ?? ''),
                    'profile_completion' => (int) ($seeker->profile_completion ?? 0),
                    'profile_views' => (int) ($seeker->profile_views ?? 0),
                    'is_profile_complete' => (bool) ($seeker->profile_completion >= 80),
                ],
                'applications' => $applicationStats,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }

    /**
     * Upload profile image.
     */
    public function uploadProfileImage(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $seeker = $this->profileService->updateProfile(
            $user,
            [],
            $request->file('profile_image')
        );

        return response()->json([
            'message' => 'Profile image uploaded successfully',
            'data' => [
                'avatar_url' => $this->profileService->resolveAvatarUrl($seeker),
                'profile_completion' => (int) $seeker->profile_completion,
            ],
        ]);
    }

    /**
     * Upload full body image (for domestic workers).
     */
    public function uploadFullBodyImage(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_body_image' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $seeker = $this->profileService->updateProfile(
            $user,
            [],
            null,
            $request->file('full_body_image')
        );

        return response()->json([
            'message' => 'Full body image uploaded successfully',
            'data' => [
                'full_body_image_url' => $this->profileService->resolveFullBodyImageUrl($seeker),
            ],
        ]);
    }

    /**
     * Get driver license details.
     */
    public function getDriverDetails(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;

        if (!$seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        // Get driver license data from both Seeker model and Resume
        $resume = $user->resume;
        $driverLicenseData = [];

        // Get data from Seeker model (primary source)
        if ($seeker->license_number) {
            $driverLicenseData['license_number'] = $seeker->license_number;
        }
        if ($seeker->license_type) {
            $driverLicenseData['license_type'] = $seeker->license_type;
        }
        if ($seeker->license_expiry_date) {
            $driverLicenseData['license_expiry_date'] = $seeker->license_expiry_date;
        }
        if ($seeker->license_issuing_country) {
            $driverLicenseData['license_issuing_country'] = $seeker->license_issuing_country;
        }
        if ($seeker->license_issuing_authority) {
            $driverLicenseData['license_issuing_authority'] = $seeker->license_issuing_authority;
        }
        if ($seeker->accident_free_years) {
            $driverLicenseData['accident_free_years'] = $seeker->accident_free_years;
        }
        if ($seeker->has_clean_driving_record !== null) {
            $driverLicenseData['has_clean_driving_record'] = $seeker->has_clean_driving_record;
        }

        // Merge with data from Resume driver_license JSON (fallback)
        if ($resume && $resume->driver_license) {
            $resumeDriverData = is_string($resume->driver_license) 
                ? json_decode($resume->driver_license, true) 
                : $resume->driver_license;
                
            if (is_array($resumeDriverData)) {
                // Only use resume data if seeker data is empty
                foreach ($resumeDriverData as $key => $value) {
                    if (!isset($driverLicenseData[$key]) && $value !== null) {
                        $driverLicenseData[$key] = $value;
                    }
                }
            }
        }

        return response()->json([
            'data' => $driverLicenseData,
        ]);
    }

    /**
     * Update driver license details.
     */
    public function updateDriverDetails(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;

        if (!$seeker) {
            return response()->json([
                'message' => 'Seeker profile not found',
            ], 404);
        }

        $validated = $request->validate([
            'license_number' => 'nullable|string|max:50',
            'license_type' => 'nullable|string|max:50',
            'license_expiry_date' => 'nullable|date',
            'license_issuing_country' => 'nullable|string|max:100',
            'license_issuing_authority' => 'nullable|string|max:150',
            'accident_free_years' => 'nullable|integer|min:0|max:50',
            'has_clean_driving_record' => 'nullable|boolean',
        ]);

        // Update Seeker model
        $seeker->update($validated);

        // Also update Resume driver_license JSON for consistency
        $resume = $user->resume;
        if ($resume) {
            $driverLicense = $resume->driver_license;
            if (is_string($driverLicense)) {
                $driverLicense = json_decode($driverLicense, true) ?? [];
            } elseif (!is_array($driverLicense)) {
                $driverLicense = [];
            }

            // Update driver_license JSON with new data
            foreach ($validated as $key => $value) {
                if ($value !== null) {
                    $driverLicense[$key] = $value;
                }
            }

            $resume->driver_license = $driverLicense;
            $resume->save();
        }

        return response()->json([
            'message' => 'Driver details updated successfully',
            'data' => $validated,
        ]);
    }
}
