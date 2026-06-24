<?php

namespace App\Observers;

use App\Models\SeekerResume;
use Illuminate\Support\Facades\Log;

/**
 * SeekerResumeObserver
 * 
 * Handles translation invalidation when SeekerResume model is updated.
 * When translatable JSON sections change, cached translations become stale
 * and must be cleared to trigger fresh API calls on next CV generation.
 */
class SeekerResumeObserver
{
    /**
     * Fields/sections that, when changed, should invalidate translations
     */
    protected array $translatableSections = [
        'basic_information',
        'professional_summary',
        'work_experience',
        'education',
        'skills',
        'languages',
        'certifications',
        'references',
    ];

    /**
     * Handle the SeekerResume "updated" event.
     */
    public function updated(SeekerResume $resume): void
    {
        // Check if any translatable section was changed
        $changedFields = array_keys($resume->getChanges());
        $translatableChanged = array_intersect($changedFields, $this->translatableSections);

        if (!empty($translatableChanged)) {
            Log::info("SeekerResumeObserver: Translatable sections changed, invalidating translations", [
                'resume_id' => $resume->id,
                'changed_sections' => $translatableChanged,
            ]);

            // Clear all cached translations
            $resume->translations = null;
            $resume->saveQuietly(); // Avoid infinite loop
        }
    }

    /**
     * Handle the SeekerResume "created" event.
     */
    public function created(SeekerResume $resume): void
    {
        // No action needed on create - translations will be generated on first request
    }

    /**
     * Handle the SeekerResume "deleted" event.
     */
    public function deleted(SeekerResume $resume): void
    {
        // No action needed - translations are deleted with the model
    }
}
