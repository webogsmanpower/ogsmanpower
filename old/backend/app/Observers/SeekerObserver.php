<?php

namespace App\Observers;

use App\Models\Seeker;
use App\Services\SeekerProfileService;
use Illuminate\Support\Facades\Log;

/**
 * SeekerObserver
 * 
 * Handles automatic profile completion recalculation and translation invalidation.
 * 
 * KEY RESPONSIBILITIES:
 * 1. Recalculate profile_completion on ANY update (via SeekerProfileService)
 * 2. Invalidate translations when translatable fields change
 * 
 * This ensures the database is ALWAYS in sync with the calculated values.
 */
class SeekerObserver
{
    /**
     * Fields that affect profile completion score
     */
    protected array $completionFields = [
        'first_name',
        'last_name',
        'profile_image_path',
        'date_of_birth',
        'current_location',
        'bio',
        'profession',
        'headline',
        'experience_years',
        'skills',
        'resume_path',
        'license_number',
        'license_expiry_date',
    ];

    /**
     * Fields that, when changed, should invalidate translations
     */
    protected array $translatableFields = [
        'bio',
        'headline',
        'first_name',
        'last_name',
        'skills',
    ];

    /**
     * Handle the Seeker "saving" event.
     * Recalculate completion BEFORE save to ensure DB is always correct.
     */
    public function saving(Seeker $seeker): void
    {
        // Skip if we're in a quiet save (to avoid infinite loop)
        if ($seeker->isDirty('profile_completion')) {
            return;
        }

        // Check if any completion-affecting field changed
        $changedFields = array_keys($seeker->getDirty());
        $completionChanged = array_intersect($changedFields, $this->completionFields);

        if (!empty($completionChanged)) {
            $profileService = app(SeekerProfileService::class);
            $completion = $profileService->calculateCompletion($seeker);
            
            $seeker->profile_completion = $completion;
            $seeker->is_profile_complete = $completion >= 80;

            Log::info("SeekerObserver: Profile completion recalculated", [
                'seeker_id' => $seeker->id,
                'changed_fields' => $completionChanged,
                'new_completion' => $completion,
            ]);
        }
    }

    /**
     * Handle the Seeker "updated" event.
     */
    public function updated(Seeker $seeker): void
    {
        // Check if any translatable field was changed
        $changedFields = array_keys($seeker->getChanges());
        $translatableChanged = array_intersect($changedFields, $this->translatableFields);

        if (!empty($translatableChanged)) {
            Log::info("SeekerObserver: Translatable fields changed, invalidating translations", [
                'seeker_id' => $seeker->id,
                'changed_fields' => $translatableChanged,
            ]);

            // Clear all cached translations
            $seeker->translations = null;
            $seeker->saveQuietly(); // Avoid infinite loop
        }
    }

    /**
     * Handle the Seeker "created" event.
     */
    public function created(Seeker $seeker): void
    {
        // Calculate initial completion score
        $profileService = app(SeekerProfileService::class);
        $completion = $profileService->calculateCompletion($seeker);
        
        $seeker->profile_completion = $completion;
        $seeker->is_profile_complete = $completion >= 80;
        $seeker->saveQuietly();

        Log::info("SeekerObserver: Initial profile completion calculated", [
            'seeker_id' => $seeker->id,
            'completion' => $completion,
        ]);
    }

    /**
     * Handle the Seeker "deleted" event.
     */
    public function deleted(Seeker $seeker): void
    {
        // No action needed - translations are deleted with the model
    }
}
