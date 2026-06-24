<?php

namespace App\Services;

use App\Models\SeekerResume;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * ResumeService
 * 
 * Handles all business logic for resume operations.
 * Controllers should delegate to this service for any non-trivial operations.
 * 
 * @package App\Services
 */
class ResumeService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * JSON columns that can be updated on a resume
     */
    private const JSON_COLUMNS = [
        'basic_information',
        'documents',
        'social_profiles',
        'professional_summary',
        'work_experience',
        'education',
        'skills',
        'languages',
        'certifications',
        'references',
        'job_preferences',
        'availability',
        'privacy_settings',
        'generated_cv',
        'resume_versions',
        'extra',
    ];

    /**
     * Updatable sections via API
     */
    private const UPDATABLE_SECTIONS = [
        'basic_information',
        'documents',
        'social_profiles',
        'professional_summary',
        'work_experience',
        'education',
        'skills',
        'languages',
        'certifications',
        'references',
        'job_preferences',
        'availability',
        'privacy_settings',
    ];

    /**
     * Get or create a resume for a user.
     * Uses caching for performance.
     *
     * @param User $user
     * @param bool $fresh Force fresh data (bypass cache)
     * @return SeekerResume
     */
    public function getOrCreateResume(User $user, bool $fresh = false): SeekerResume
    {
        $cacheKey = "resume_user_{$user->id}";

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $defaults = array_fill_keys(self::JSON_COLUMNS, []);
            $defaults['profile_completion'] = 0;
            $defaults['seeker_id'] = optional($user->seeker)->id;

            $resume = SeekerResume::firstOrCreate(
                ['user_id' => $user->id],
                $defaults
            );

            // Calculate profile completion if it's 0 (newly created or not calculated)
            if ($resume->profile_completion === 0) {
                $resume->calculateProfileCompletion();
            }

            // Link seeker if not already linked
            if (!$resume->seeker_id && $user->seeker) {
                $resume->seeker_id = $user->seeker->id;
                $resume->save();
            }

            return $resume;
        });
    }

    /**
     * Get full aggregated data for a user (user + resume + onboarding status).
     * Optimized single-query approach with eager loading.
     *
     * @param User $user
     * @param bool $fresh Force fresh data
     * @return array
     */
    public function getFullResumeData(User $user, bool $fresh = false): array
    {
        $cacheKey = "resume_full_{$user->id}";

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            // Eager load all relationships in one query
            $user->load('seeker');
            
            $resume = $this->getOrCreateResume($user, true);
            $resumeData = $this->appendUrlsToResumeData($resume->toArray());
            
            $seeker = $user->seeker;
            $isProfileComplete = $seeker?->is_profile_complete ?? false;
            $isOnboardingCompleted = $user->is_onboarding_completed ?? false;
            
            return [
                'user' => $user,
                'resume' => $resumeData,
                'profile_completion' => $resume->profile_completion,
                'onboarding' => [
                    'is_completed' => $isOnboardingCompleted,
                    'is_profile_complete' => $isProfileComplete,
                    'can_complete' => $isProfileComplete && !$isOnboardingCompleted,
                ],
            ];
        });
    }

    /**
     * Update a specific section of the resume.
     *
     * @param User $user
     * @param string $section
     * @param array $data
     * @return SeekerResume
     * @throws \InvalidArgumentException
     */
    public function updateSection(User $user, string $section, array $data): SeekerResume
    {
        $sectionKey = $this->normalizeSectionKey($section);

        if (!in_array($sectionKey, self::UPDATABLE_SECTIONS, true)) {
            throw new \InvalidArgumentException("Invalid resume section: {$section}");
        }

        $resume = $this->getOrCreateResume($user, true);
        $resume->setAttribute($sectionKey, $data);
        $resume->save();
        $resume->calculateProfileCompletion();

        // Sync critical fields to User/Seeker models
        if ($sectionKey === 'basic_information') {
            $this->syncBasicInfoToModels($user, $data);
        }

        $this->invalidateCache($user->id);

        return $resume;
    }

    /**
     * Patch a section with partial data (merge with existing).
     *
     * @param User $user
     * @param string $section
     * @param array $data
     * @param int|null $entryIndex For multi-entry sections
     * @return SeekerResume
     */
    public function patchSection(User $user, string $section, array $data, ?int $entryIndex = null): SeekerResume
    {
        $sectionKey = $this->normalizeSectionKey($section);

        if (!in_array($sectionKey, self::UPDATABLE_SECTIONS, true)) {
            throw new \InvalidArgumentException("Invalid resume section: {$section}");
        }

        $resume = $this->getOrCreateResume($user, true);
        $existingData = $resume->getAttribute($sectionKey);

        // Handle array sections (multi-entry like work_experience, education)
        if (is_array($existingData) && isset($existingData[0]) && is_array($existingData[0])) {
            if ($entryIndex !== null) {
                if (isset($existingData[$entryIndex])) {
                    $existingData[$entryIndex] = array_merge($existingData[$entryIndex], $data);
                } else {
                    $existingData[$entryIndex] = $data;
                }
            } else {
                $existingData = $data;
            }
        } else {
            // Single-entry section - merge fields
            $existingData = is_array($existingData) ? array_merge($existingData, $data) : $data;
        }

        $resume->setAttribute($sectionKey, $existingData);
        $resume->save();

        $this->invalidateCache($user->id);

        return $resume;
    }

    /**
     * Append full URLs to file paths in resume data.
     *
     * @param array $resumeData
     * @return array
     */
    public function appendUrlsToResumeData(array $resumeData): array
    {
        $baseUrl = config('app.url');

        // Handle basic information profile photo
        // Don't store full URLs in the database - store raw paths only
        // ResumeResource will handle URL resolution when returning data
        // The profile_photo field should contain only the relative path

        // Handle full body photo
        if (isset($resumeData['full_body_photo']) && $resumeData['full_body_photo']) {
            $photoPath = $resumeData['full_body_photo'];
            if (!str_starts_with($photoPath, 'http')) {
                $resumeData['full_body_photo'] = $baseUrl . Storage::disk('public')->url($photoPath);
            }
        }

        // Handle documents array
        if (isset($resumeData['documents']) && is_array($resumeData['documents'])) {
            foreach ($resumeData['documents'] as $key => $document) {
                if (isset($document['file_path']) && $document['file_path']) {
                    $filePath = $document['file_path'];
                    if (!str_starts_with($filePath, 'http')) {
                        $resumeData['documents'][$key]['file_url'] = $baseUrl . Storage::disk('public')->url($filePath);
                    }
                }
            }
        }

        return $resumeData;
    }

    /**
     * Sync basic information fields to User and Seeker models.
     *
     * @param User $user
     * @param array $data
     * @return void
     */
    private function syncBasicInfoToModels(User $user, array $data): void
    {
        $userFieldsToSync = false;

        if (isset($data['date_of_birth'])) {
            $user->date_of_birth = $data['date_of_birth'];
            $userFieldsToSync = true;
        }

        if (isset($data['phone'])) {
            $user->mobile = $data['phone'];
            $userFieldsToSync = true;
        }

        if ($userFieldsToSync) {
            $user->save();
        }

        $seeker = $user->seeker;
        if ($seeker) {
            $seekerFields = ['first_name', 'last_name', 'headline', 'date_of_birth', 'current_location'];
            foreach ($seekerFields as $field) {
                if (isset($data[$field])) {
                    $seeker->$field = $data[$field];
                }
            }
            $seeker->is_profile_complete = true;
            
            // Calculate and update profile completion
            $seeker->calculateProfileCompletion();
        }
    }

    /**
     * Invalidate all caches for a user.
     *
     * @param int $userId
     * @return void
     */
    public function invalidateCache(int $userId): void
    {
        Cache::forget("resume_user_{$userId}");
        Cache::forget("resume_full_{$userId}");
    }

    /**
     * Normalize section key from URL format to database column format.
     *
     * @param string $section
     * @return string
     */
    private function normalizeSectionKey(string $section): string
    {
        return str_replace('-', '_', strtolower($section));
    }

    /**
     * Get allowed keys for resume updates.
     *
     * @return array
     */
    public function getAllowedKeys(): array
    {
        return array_merge([
            'profile_completion',
            'primary_language',
            'is_rtl',
            'resume_format',
        ], self::JSON_COLUMNS);
    }

    /**
     * Check if a section key is valid.
     *
     * @param string $section
     * @return bool
     */
    public function isValidSection(string $section): bool
    {
        return in_array($this->normalizeSectionKey($section), self::UPDATABLE_SECTIONS, true);
    }
}
