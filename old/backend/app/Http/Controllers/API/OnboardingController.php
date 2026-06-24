<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * OnboardingController
 * 
 * Handles user onboarding flow including completion status
 * and validation of required onboarding sections.
 * 
 * @package App\Http\Controllers\API
 */
class OnboardingController extends Controller
{
    /**
     * Complete the onboarding process for a user.
     * 
     * Validates that required sections (Basic Information and Job Preferences)
     * are completed, then marks onboarding as complete.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Success message with updated user data
     * @throws ValidationException If validation fails
     */
    public function completeOnboarding(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load user with seeker and resume relationships
        $user = User::with(['seeker', 'seekerResume'])->find($user->id);

        // Validate required sections are completed
        $this->validateOnboardingSections($user);

        // Mark onboarding as completed
        $user->update([
            'is_onboarding_completed' => true,
        ]);

        // Also mark seeker profile as complete
        if ($user->seeker) {
            $user->seeker->is_profile_complete = true;
            $user->seeker->save();
        }

        return response()->json([
            'message' => 'Onboarding completed successfully.',
            'user' => $user->fresh(['seeker', 'seekerResume']),
            'debug_seeker' => $user->seeker,
        ]);
    }

    /**
     * Get the current onboarding status of the user.
     * 
     * Returns completion status and validation status for required sections.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Onboarding status information
     */
    public function getOnboardingStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load user with seeker and resume relationships
        $user = User::with(['seeker', 'seekerResume'])->find($user->id);

        $status = [
            'is_completed' => $user->is_onboarding_completed,
            'sections' => [
                'basic_information' => $this->isBasicInformationComplete($user),
                'job_preferences' => $this->isJobPreferencesComplete($user),
            ],
            'can_complete' => false,
        ];

        // User can complete onboarding if all required sections are complete
        $status['can_complete'] = $status['sections']['basic_information']['is_complete'] && 
                                $status['sections']['job_preferences']['is_complete'];

        return response()->json($status);
    }

    /**
     * Validate that all required onboarding sections are completed.
     * 
     * @param User $user The user to validate
     * @throws ValidationException If any required section is incomplete
     */
    protected function validateOnboardingSections(User $user): void
    {
        $errors = [];

        // Check Basic Information
        $basicInfoStatus = $this->isBasicInformationComplete($user);
        if (!$basicInfoStatus['is_complete']) {
            $errors['basic_information'] = 'Basic Information section is incomplete. ' . 
                                         implode(', ', $basicInfoStatus['missing_fields']);
        }

        // Check Job Preferences
        $jobPrefsStatus = $this->isJobPreferencesComplete($user);
        if (!$jobPrefsStatus['is_complete']) {
            $errors['job_preferences'] = 'Job Preferences section is incomplete. ' . 
                                        implode(', ', $jobPrefsStatus['missing_fields']);
        }

        if (!empty($errors)) {
            \Log::warning('Onboarding validation failed', [
                'user_id' => $user->id,
                'missing_sections' => $errors,
                'resume_job_preferences' => $user->seekerResume?->job_preferences,
                'resume_columns' => $user->seekerResume?->toArray(),
            ]);
            throw \ValidationException::withMessages($errors);
        }
    }

    /**
     * Check if Basic Information section is complete.
     * 
     * @param User $user The user to check
     * @return array Completion status and missing fields
     */
    protected function isBasicInformationComplete(User $user): array
    {
        $missing = [];
        $seeker = $user->seeker;

        // Debug logging to see what data is available
        \Log::info('Checking basic information completeness', [
            'user_id' => $user->id,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'date_of_birth' => $user->date_of_birth,
            'seeker_first_name' => $seeker?->first_name,
            'seeker_last_name' => $seeker?->last_name,
        ]);

        // Required basic information fields
        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'mobile' => 'Mobile Number',
            'date_of_birth' => 'Date of Birth',
        ];

        // Check user fields
        foreach (['email', 'mobile', 'date_of_birth'] as $field) {
            if (empty($user->$field)) {
                \Log::info("Missing field: {$field}", ['value' => $user->$field]);
                $missing[] = $requiredFields[$field];
            }
        }

        // Check seeker fields
        if ($seeker) {
            foreach (['first_name', 'last_name'] as $field) {
                if (empty($seeker->$field)) {
                    $missing[] = $requiredFields[$field];
                }
            }
        } else {
            // If no seeker record, add both name fields as missing
            $missing[] = $requiredFields['first_name'];
            $missing[] = $requiredFields['last_name'];
        }

        return [
            'is_complete' => empty($missing),
            'missing_fields' => $missing,
        ];
    }

    /**
     * Check if Job Preferences section is complete.
     * 
     * @param User $user The user to check
     * @return array Completion status and missing fields
     */
    protected function isJobPreferencesComplete(User $user): array
    {
        $missing = [];
        $resume = $user->seekerResume;

        if (!$resume) {
            return [
                'is_complete' => false,
                'missing_fields' => ['Job Preferences data not found'],
            ];
        }

        // Prefer the consolidated job_preferences JSON column but also look at direct columns if present
        $jobPreferences = $resume->job_preferences ?? [];

        // Map actual field names to expected field names
        $fieldMappings = [
            'preferred_location' => 'Preferred Locations',
            'preferred_locations' => 'Preferred Locations',
            'employment_type' => 'Job Types',
            'job_types' => 'Job Types',
            'expected_salary' => 'Salary Expectations',
            'salary_expectations' => 'Salary Expectations',
        ];

        // Check for presence of any of the mapped fields
        $checks = [
            'preferred_locations' => false,
            'job_types' => false,
            'salary_expectations' => false,
        ];

        foreach ($fieldMappings as $actualField => $label) {
            $value = $jobPreferences[$actualField] ?? $resume->$actualField ?? null;
            
            if (!empty($value)) {
                // Map back to the standard field name
                if (in_array($actualField, ['preferred_location', 'preferred_locations'])) {
                    $checks['preferred_locations'] = true;
                } elseif (in_array($actualField, ['employment_type', 'job_types'])) {
                    $checks['job_types'] = true;
                } elseif (in_array($actualField, ['expected_salary', 'salary_expectations'])) {
                    $checks['salary_expectations'] = true;
                }
            }
        }

        // Determine missing fields
        if (!$checks['preferred_locations']) {
            $missing[] = 'Preferred Locations';
        }
        if (!$checks['job_types']) {
            $missing[] = 'Job Types';
        }
        if (!$checks['salary_expectations']) {
            $missing[] = 'Salary Expectations';
        }

        return [
            'is_complete' => empty($missing),
            'missing_fields' => $missing,
        ];
    }
}
