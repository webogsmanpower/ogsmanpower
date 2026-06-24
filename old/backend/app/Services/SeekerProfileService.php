<?php

namespace App\Services;

use App\Models\Seeker;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SeekerProfileService
 * 
 * THE SINGLE SOURCE OF TRUTH for all Seeker profile operations.
 * All business logic for profile management is centralized here.
 * Controllers should ONLY handle HTTP concerns and delegate to this service.
 * 
 * Responsibilities:
 * - Profile completion calculation (centralized math)
 * - Avatar URL resolution (the ONLY place that decides URL format)
 * - Profile updates (handles transactions, file uploads, score updates)
 * 
 * @package App\Services
 */
class SeekerProfileService
{
    /**
     * Profile completion weights - centralized scoring configuration
     */
    private const COMPLETION_WEIGHTS = [
        // Basic Information (40 points total)
        'first_name' => 5,
        'last_name' => 5,
        'mobile' => 5,
        'profile_image' => 10,
        'date_of_birth' => 5,
        'current_location' => 5,
        'bio' => 5,
        
        // Professional Details (50 points total)
        'profession' => 10,
        'headline' => 10,
        'experience_years' => 10,
        'skills' => 10,
        'resume_path' => 10,
        
        // Additional Information (10 points total)
        'license_number' => 5,
        'license_expiry_date' => 5,
    ];

    /**
     * Default avatar path when no image is uploaded
     */
    private const DEFAULT_AVATAR = '/assets/images/default-avatar.svg';

    private ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Calculate profile completion percentage.
     * 
     * THE SINGLE SOURCE OF TRUTH for completion calculation.
     * This method is called on every profile update to ensure consistency.
     *
     * @param Seeker $seeker
     * @return int Completion percentage (0-100)
     */
    public function calculateCompletion(Seeker $seeker): int
    {
        // Ensure user relationship is loaded for mobile check
        if (!$seeker->relationLoaded('user')) {
            $seeker->load('user');
        }

        $score = 0;

        // Basic Information
        if (!empty($seeker->first_name)) {
            $score += self::COMPLETION_WEIGHTS['first_name'];
        }
        if (!empty($seeker->last_name)) {
            $score += self::COMPLETION_WEIGHTS['last_name'];
        }
        if (!empty($seeker->user?->mobile)) {
            $score += self::COMPLETION_WEIGHTS['mobile'];
        }
        if (!empty($seeker->profile_image_path)) {
            $score += self::COMPLETION_WEIGHTS['profile_image'];
        }
        if (!empty($seeker->date_of_birth)) {
            $score += self::COMPLETION_WEIGHTS['date_of_birth'];
        }
        if (!empty($seeker->current_location)) {
            $score += self::COMPLETION_WEIGHTS['current_location'];
        }
        if (!empty($seeker->bio)) {
            $score += self::COMPLETION_WEIGHTS['bio'];
        }

        // Professional Details
        if (!empty($seeker->profession)) {
            $score += self::COMPLETION_WEIGHTS['profession'];
        }
        if (!empty($seeker->headline)) {
            $score += self::COMPLETION_WEIGHTS['headline'];
        }
        if (!empty($seeker->experience_years)) {
            $score += self::COMPLETION_WEIGHTS['experience_years'];
        }
        if (!empty($seeker->skills) && count($seeker->skills) > 0) {
            $score += self::COMPLETION_WEIGHTS['skills'];
        }
        if (!empty($seeker->resume_path)) {
            $score += self::COMPLETION_WEIGHTS['resume_path'];
        }

        // Additional Information (Driver-specific)
        if (!empty($seeker->license_number)) {
            $score += self::COMPLETION_WEIGHTS['license_number'];
        }
        if (!empty($seeker->license_expiry_date)) {
            $score += self::COMPLETION_WEIGHTS['license_expiry_date'];
        }

        return min($score, 100);
    }

    /**
     * Resolve the avatar URL for a seeker.
     * 
     * THE SINGLE SOURCE OF TRUTH for avatar URL resolution.
     * Handles: local storage, S3, external URLs, and default fallback.
     *
     * @param Seeker|null $seeker
     * @return string Always returns a valid URL (never null)
     */
    public function resolveAvatarUrl(?Seeker $seeker): string
    {
        if (!$seeker) {
            return $this->getDefaultAvatarUrl();
        }

        $path = $seeker->profile_image_path;

        // No image uploaded - return default
        if (empty($path)) {
            // Check resume for profile photo as fallback
            if ($seeker->relationLoaded('resume') && $seeker->resume) {
                $resumePhoto = $seeker->resume->basic_information['profile_photo'] ?? null;
                if ($resumePhoto) {
                    return $this->buildStorageUrl($resumePhoto);
                }
            }
            return $this->getDefaultAvatarUrl();
        }

        // Already a full URL (S3, external, etc.)
        if ($this->isFullUrl($path)) {
            return $path;
        }

        // Local storage path - build full URL
        return $this->buildStorageUrl($path);
    }

    /**
     * Resolve the full body image URL for a seeker.
     *
     * @param Seeker|null $seeker
     * @return string|null Returns null if no image
     */
    public function resolveFullBodyImageUrl(?Seeker $seeker): ?string
    {
        if (!$seeker || empty($seeker->full_body_image_path)) {
            return null;
        }

        $path = $seeker->full_body_image_path;

        if ($this->isFullUrl($path)) {
            return $path;
        }

        return $this->buildStorageUrl($path);
    }

    /**
     * Update seeker profile with transaction safety.
     * 
     * Handles the complete update flow:
     * 1. Validates and processes data
     * 2. Handles file uploads
     * 3. Updates database in transaction
     * 4. Recalculates completion score
     *
     * @param User $user
     * @param array $data Profile data to update
     * @param UploadedFile|null $profileImage
     * @param UploadedFile|null $fullBodyImage
     * @return Seeker Updated seeker model
     * @throws \Exception
     */
    public function updateProfile(
        User $user,
        array $data,
        ?UploadedFile $profileImage = null,
        ?UploadedFile $fullBodyImage = null
    ): Seeker {
        return DB::transaction(function () use ($user, $data, $profileImage, $fullBodyImage) {
            $seeker = $user->seeker;

            if (!$seeker) {
                throw new \Exception('Seeker profile not found for user');
            }

            // Handle profile image upload
            if ($profileImage) {
                $path = $this->imageService->uploadProfileImage($profileImage, $user);
                $data['profile_image_path'] = $path;

                Log::info('Profile image uploaded', [
                    'user_id' => $user->id,
                    'path' => $path,
                ]);
            }

            // Handle full body image upload
            if ($fullBodyImage) {
                $result = $this->imageService->uploadFullBodyPhoto($fullBodyImage, $user->id);
                $data['full_body_image_path'] = $result['path'];

                Log::info('Full body image uploaded', [
                    'user_id' => $user->id,
                    'path' => $result['path'],
                ]);
            }

            // Update seeker fields
            $seeker->fill($this->filterSeekerFields($data));
            $seeker->save();

            // Sync mobile to user if provided
            if (isset($data['mobile']) || isset($data['phone'])) {
                $user->mobile = $data['mobile'] ?? $data['phone'];
                $user->save();
            }

            // Recalculate completion score immediately
            $completion = $this->calculateCompletion($seeker);
            $seeker->profile_completion = $completion;
            $seeker->is_profile_complete = $completion >= 80;
            $seeker->saveQuietly(); // Avoid triggering observer again

            Log::info('Profile updated', [
                'seeker_id' => $seeker->id,
                'completion' => $completion,
            ]);

            return $seeker->fresh(['user', 'resume']);
        });
    }

    /**
     * Get profile data formatted for API response.
     * 
     * Returns a standardized structure that the frontend can rely on.
     *
     * @param Seeker $seeker
     * @return array
     */
    public function getProfileData(Seeker $seeker): array
    {
        $seeker->load(['user', 'resume']);

        return [
            'id' => (int) $seeker->id,
            'user_id' => (int) $seeker->user_id,
            'first_name' => (string) ($seeker->first_name ?? ''),
            'last_name' => (string) ($seeker->last_name ?? ''),
            'full_name' => $this->resolveFullName($seeker),
            'email' => (string) ($seeker->user?->email ?? ''),
            'mobile' => (string) ($seeker->user?->mobile ?? ''),
            'avatar_url' => $this->resolveAvatarUrl($seeker),
            'full_body_image_url' => $this->resolveFullBodyImageUrl($seeker),
            'profession' => (string) ($seeker->profession ?? ''),
            'headline' => (string) ($seeker->headline ?? ''),
            'bio' => (string) ($seeker->bio ?? ''),
            'date_of_birth' => $seeker->date_of_birth?->format('Y-m-d'),
            'current_location' => (string) ($seeker->current_location ?? ''),
            'experience_years' => (int) ($seeker->experience_years ?? 0),
            'skills' => $seeker->skills ?? [],
            'profile_completion' => (int) ($seeker->profile_completion ?? 0),
            'is_profile_complete' => (bool) ($seeker->profile_completion >= 80),
            'profile_views' => (int) ($seeker->profile_views ?? 0),
            
            // Driver-specific
            'license_number' => $seeker->license_number,
            'license_expiry_date' => $seeker->license_expiry_date?->format('Y-m-d'),
            'license_issuing_country' => $seeker->license_issuing_country,
            'license_type' => $seeker->license_type,
            'has_clean_driving_record' => (bool) $seeker->has_clean_driving_record,
            
            // Domestic worker specific
            'number_of_children' => (int) ($seeker->number_of_children ?? 0),
            'skill_washing' => (bool) $seeker->skill_washing,
            'skill_cooking' => (bool) $seeker->skill_cooking,
            'skill_babysitting' => (bool) $seeker->skill_babysitting,
            'skill_cleaning' => (bool) $seeker->skill_cleaning,
        ];
    }

    /**
     * Resolve full name with fallback.
     * Never returns empty string.
     *
     * @param Seeker|null $seeker
     * @return string
     */
    public function resolveFullName(?Seeker $seeker): string
    {
        if (!$seeker) {
            return 'User';
        }

        $firstName = trim($seeker->first_name ?? '');
        $lastName = trim($seeker->last_name ?? '');

        if ($firstName || $lastName) {
            return trim("{$firstName} {$lastName}");
        }

        // Fallback to user name or email
        if ($seeker->relationLoaded('user') && $seeker->user) {
            if ($seeker->user->name) {
                return $seeker->user->name;
            }
            // Use email prefix as last resort
            $email = $seeker->user->email ?? '';
            if ($email) {
                return explode('@', $email)[0];
            }
        }

        return 'User';
    }

    /**
     * Recalculate and persist completion score.
     * 
     * Call this after any profile update to ensure DB is in sync.
     *
     * @param Seeker $seeker
     * @return int New completion score
     */
    public function recalculateAndPersist(Seeker $seeker): int
    {
        $completion = $this->calculateCompletion($seeker);
        
        $seeker->profile_completion = $completion;
        $seeker->is_profile_complete = $completion >= 80;
        $seeker->saveQuietly();

        return $completion;
    }

    /**
     * Check if a path is already a full URL.
     *
     * @param string $path
     * @return bool
     */
    private function isFullUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    /**
     * Build a storage URL from a relative path.
     *
     * @param string $path
     * @return string
     */
    private function buildStorageUrl(string $path): string
    {
        // Remove leading slashes for consistency
        $cleanPath = ltrim($path, '/');
        
        // Use Laravel's url() helper with storage path
        return url('storage/' . $cleanPath);
    }

    /**
     * Get the default avatar URL.
     *
     * @return string
     */
    private function getDefaultAvatarUrl(): string
    {
        return url(self::DEFAULT_AVATAR);
    }

    /**
     * Filter data to only include valid seeker fields.
     *
     * @param array $data
     * @return array
     */
    private function filterSeekerFields(array $data): array
    {
        $allowedFields = [
            'first_name',
            'last_name',
            'profession',
            'headline',
            'date_of_birth',
            'current_location',
            'experience_years',
            'skills',
            'bio',
            'resume_path',
            'profile_image_path',
            'full_body_image_path',
            'license_number',
            'license_expiry_date',
            'license_issuing_country',
            'license_issuing_authority',
            'license_type',
            'accident_free_years',
            'has_clean_driving_record',
            'number_of_children',
            'skill_washing',
            'skill_cooking',
            'skill_babysitting',
            'skill_cleaning',
        ];

        return array_intersect_key($data, array_flip($allowedFields));
    }
}
