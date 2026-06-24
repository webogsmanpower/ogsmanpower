<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SeekerResume;
use App\Models\Seeker;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * ResumeController
 * 
 * Handles all resume-related API operations for job seekers.
 * Implements caching for improved performance and reduced database load.
 * 
 * @package App\Http\Controllers\API
 */

class ResumeController extends Controller
{
    /**
     * Resume sections that can be individually updated via the API.
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
        'driver_license',
    ];

    /**
     * JSON columns initialised when creating a resume.
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
        'driver_license',
        'security_guard_details',
        'generated_cv',
        'resume_versions',
        'extra',
    ];

    /**
     * Append full URLs to file paths in resume data.
     * 
     * Converts relative storage paths to absolute URLs for frontend consumption.
     * Handles basic_information profile photos and document paths.
     * 
     * @param array $resumeData The resume data array
     * @return array Resume data with URLs converted to absolute paths
     */
    private function appendUrlsToResumeData(array $resumeData): array
    {
        // Handle basic information profile photo
        if (isset($resumeData['basic_information']['profile_photo'])) {
            // Don't store full URLs in the database - store raw paths only
            // ResumeResource will handle URL resolution when returning data
            // The profile_photo field should contain only the relative path
        }

        // Handle full body photo
        if (isset($resumeData['full_body_photo']) && $resumeData['full_body_photo']) {
            $photoPath = $resumeData['full_body_photo'];
            if (!str_starts_with($photoPath, 'http')) {
                $resumeData['full_body_photo'] = Storage::url($photoPath);
            }
        }

        // Handle documents array
        if (isset($resumeData['documents']) && is_array($resumeData['documents'])) {
            foreach ($resumeData['documents'] as $key => $document) {
                if (isset($document['file_path']) && $document['file_path']) {
                    $filePath = $document['file_path'];
                    if (!str_starts_with($filePath, 'http')) {
                        $resumeData['documents'][$key]['file_url'] = Storage::url($filePath);
                    }
                }
            }
        }

        return $resumeData;
    }

    /**
     * Return the authenticated user's resume payload.
     * 
     * Implements caching with a 5-minute TTL to reduce database load.
     * Cache is invalidated on any resume update.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Resume data with profile completion percentage
     */
    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cacheKey = "resume_user_{$userId}";
        
        // Cache resume data for 5 minutes to reduce DB load
        $resume = Cache::remember($cacheKey, 300, function () use ($request) {
            return $this->firstOrCreateResume($request);
        });

        // Get seeker data for domestic worker fields
        $seeker = $request->user()->seeker;

        // Convert resume to array and append URLs
        $resumeData = $resume->toArray();
        $resumeData = $this->appendUrlsToResumeData($resumeData);

        // CRITICAL: Preserve resume-specific fields before merging seeker fields
        $resumeLanguages = $resumeData['languages'] ?? null;
        $resumePrimaryLanguage = $resumeData['primary_language'] ?? null;

        // Merge ALL seeker fields into resume data - CRITICAL for CV templates
        if ($seeker) {
            $resumeData = array_merge($resumeData, $this->getSeekerFieldsForResume($seeker, $request->user()));
        }

        // Restore resume-specific fields that might be overwritten
        if ($resumeLanguages !== null) {
            $resumeData['languages'] = $resumeLanguages;
        }
        if ($resumePrimaryLanguage !== null) {
            $resumeData['primary_language'] = $resumePrimaryLanguage;
        }

        return response()->json([
            'resume' => $resumeData,
            'profile_completion' => $resume->profile_completion,
        ]);
    }

    /**
     * Return ALL resume data in a single aggregated response.
     * 
     * Optimized endpoint that returns user, resume, and onboarding status
     * in one request to eliminate multiple API calls on page load.
     * Uses eager loading and caching for maximum performance.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Aggregated data with user, resume, and onboarding status
     */
    public function full(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $cacheKey = "resume_full_{$userId}";
        
        // Cache aggregated data for 5 minutes
        $data = Cache::remember($cacheKey, 300, function () use ($request, $user) {
            // Eager load all relationships in one query
            $user->load('seeker');
            
            $resume = $this->firstOrCreateResume($request);
            
            // Convert resume to array and append URLs
            $resumeData = $resume->toArray();
            $resumeData = $this->appendUrlsToResumeData($resumeData);
            
            // CRITICAL: Preserve resume-specific fields before merging seeker fields
            $resumeLanguages = $resumeData['languages'] ?? null;
            $resumePrimaryLanguage = $resumeData['primary_language'] ?? null;
            
            // Calculate onboarding status
            $seeker = $user->seeker;
            
            // Merge ALL seeker fields into resume data - CRITICAL for CV templates
            if ($seeker) {
                $resumeData = array_merge($resumeData, $this->getSeekerFieldsForResume($seeker, $user));
            }
            
            // Restore resume-specific fields that might be overwritten
            if ($resumeLanguages !== null) {
                $resumeData['languages'] = $resumeLanguages;
            }
            if ($resumePrimaryLanguage !== null) {
                $resumeData['primary_language'] = $resumePrimaryLanguage;
            }
            $isProfileComplete = $seeker?->is_profile_complete ?? false;
            $isOnboardingCompleted = $user->is_onboarding_completed ?? false;
            
            return [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'mobile' => $user->mobile,
                    'is_onboarding_completed' => $user->is_onboarding_completed,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'resume' => $resumeData,
                'profile_completion' => $resume->profile_completion,
                'onboarding' => [
                    'is_completed' => $isOnboardingCompleted,
                    'is_profile_complete' => $isProfileComplete,
                    'can_complete' => $isProfileComplete && !$isOnboardingCompleted,
                ],
            ];
        });

        return response()->json($data);
    }

    /**
     * Patch a single field or small set of fields in a section.
     * 
     * Optimized for auto-save - only updates the specific fields provided,
     * merging with existing data rather than replacing the entire section.
     * 
     * @param Request $request The incoming HTTP request with field data
     * @param string $section The section identifier
     * @return JsonResponse Updated status with timestamp
     */
    public function patchSection(Request $request, string $section): JsonResponse
    {
        $sectionKey = $this->normalizeSectionKey($section);
        $userId = $request->user()->id;

        $payload = $request->input();
        
        // Allow empty payloads for initialization - just return success without changes
        if (!is_array($payload) || empty($payload)) {
            return response()->json([
                'success' => true,
                'message' => 'No data to update',
                'section' => $sectionKey,
                'saved_at' => now()->toISOString()
            ]);
        }

        $resume = $this->firstOrCreateResume($request);

        if (!in_array($sectionKey, self::UPDATABLE_SECTIONS, true)) {
            $extra = $resume->extra;
            $extra = is_array($extra) ? $extra : [];
            $existingData = $extra[$sectionKey] ?? null;

            if (is_array($existingData) && isset($existingData[0]) && is_array($existingData[0])) {
                if (isset($payload['entry_index']) && isset($payload['data'])) {
                    $entryIndex = (int) $payload['entry_index'];
                    $entryData = $payload['data'];

                    if (isset($existingData[$entryIndex])) {
                        $existingData[$entryIndex] = array_merge($existingData[$entryIndex], $entryData);
                    } else {
                        $existingData[$entryIndex] = $entryData;
                    }
                } else {
                    $existingData = $payload;
                }
            } else {
                $existingData = is_array($existingData) ? array_merge($existingData, $payload) : $payload;
            }

            $extra[$sectionKey] = $existingData;
            $resume->extra = $extra;
            $resume->save();

            Cache::forget("resume_user_{$userId}");
            Cache::forget("resume_full_{$userId}");

            return response()->json([
                'success' => true,
                'section' => $sectionKey,
                'saved_at' => now()->toISOString(),
            ]);
        }

        $existingData = $resume->getAttribute($sectionKey);

        // Handle array sections (multi-entry like work_experience, education)
        if (is_array($existingData) && isset($existingData[0]) && is_array($existingData[0])) {
            // This is a multi-entry section - expect payload with entry_index
            if (isset($payload['entry_index']) && isset($payload['data'])) {
                $entryIndex = (int) $payload['entry_index'];
                $entryData = $payload['data'];
                
                if (isset($existingData[$entryIndex])) {
                    $existingData[$entryIndex] = array_merge($existingData[$entryIndex], $entryData);
                } else {
                    $existingData[$entryIndex] = $entryData;
                }
            } else {
                // Replace entire array
                $existingData = $payload;
            }
        } else {
            // Single-entry section - merge fields
            $existingData = is_array($existingData) ? array_merge($existingData, $payload) : $payload;
        }

        $resume->setAttribute($sectionKey, $existingData);
        $resume->save();
        
        // Invalidate both caches
        Cache::forget("resume_user_{$userId}");
        Cache::forget("resume_full_{$userId}");

        return response()->json([
            'success' => true,
            'section' => $sectionKey,
            'saved_at' => now()->toISOString(),
        ]);
    }

    /**
     * Create or update the entire resume payload in one request.
     * 
     * Invalidates cache after successful update to ensure data consistency.
     * Only updates allowed fields to prevent mass assignment vulnerabilities.
     * 
     * @param Request $request The incoming HTTP request with resume data
     * @return JsonResponse Updated resume data with success message
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractSectionPayload($request);
        $userId = $request->user()->id;

        $resume = $this->firstOrCreateResume($request);
        $allowedKeys = array_merge([
            'profile_completion',
            'primary_language',
            'is_rtl',
            'resume_format',
        ], self::JSON_COLUMNS);

        foreach ($payload as $key => $value) {
            if (in_array($key, $allowedKeys, true)) {
                $resume->setAttribute($key, $value);
            }
        }

        $resume->save();
        $resume->calculateProfileCompletion();
        
        // Invalidate cache after update
        Cache::forget("resume_user_{$userId}");

        return response()->json([
            'message' => 'Resume saved successfully.',
            'resume' => $resume,
            'profile_completion' => $resume->profile_completion,
        ]);
    }

    /**
     * Update a single resume section (basic info, documents, etc.).
     * 
     * Validates section key against allowed sections list.
     * Performs email uniqueness validation for basic_information section.
     * Validates enhanced field structures for work experience, education, certifications, and references.
     * Invalidates cache after successful update.
     * 
     * @param Request $request The incoming HTTP request with section data
     * @param string $section The section identifier (e.g., 'basic-information')
     * @return JsonResponse Updated resume data with success message
     * @throws ValidationException If section is invalid or email already exists
     */
    public function updateSection(Request $request, string $section): JsonResponse
    {
        $sectionKey = $this->normalizeSectionKey($section);
        $userId = $request->user()->id;

        $payload = $this->extractSectionPayload($request);

        $resume = $this->firstOrCreateResume($request);

        if (!in_array($sectionKey, self::UPDATABLE_SECTIONS, true)) {
            $extra = $resume->extra;
            $extra = is_array($extra) ? $extra : [];
            $extra[$sectionKey] = $payload;
            $resume->extra = $extra;
            $resume->save();
            $resume->calculateProfileCompletion();

            Cache::forget("resume_user_{$userId}");
            Cache::forget("resume_full_{$userId}");

            return response()->json([
                'message' => 'Section updated successfully.',
                'resume' => $resume,
                'profile_completion' => $resume->profile_completion,
            ]);
        }

        // Validate section-specific data
        $this->validateSectionData($sectionKey, $payload, $request);

        $resume->setAttribute($sectionKey, $payload);
        $resume->save();
        
        $resume->calculateProfileCompletion();
        
        // Sync driver_license data to Seeker model
        if ($sectionKey === 'driver_license') {
            $user = $request->user();
            $seeker = $user->seeker;
            
            if ($seeker) {
                if (isset($payload['license_number'])) {
                    $seeker->license_number = $payload['license_number'];
                }
                if (isset($payload['license_type'])) {
                    $seeker->license_type = $payload['license_type'];
                }
                if (isset($payload['license_expiry_date'])) {
                    $seeker->license_expiry_date = $payload['license_expiry_date'];
                }
                if (isset($payload['license_issuing_country'])) {
                    $seeker->license_issuing_country = $payload['license_issuing_country'];
                }
                if (isset($payload['license_issuing_authority'])) {
                    $seeker->license_issuing_authority = $payload['license_issuing_authority'];
                }
                if (isset($payload['accident_free_years'])) {
                    $seeker->accident_free_years = $payload['accident_free_years'];
                }
                if (array_key_exists('has_clean_driving_record', $payload)) {
                    $seeker->has_clean_driving_record = (bool) $payload['has_clean_driving_record'];
                }
                $seeker->save();
                
                \Log::info('Driver license data synced to Seeker model', [
                    'user_id' => $user->id,
                    'seeker_id' => $seeker->id,
                ]);
            }
        }
        
        // Update seeker profile and mark as complete if basic information is saved
        if ($sectionKey === 'basic_information') {
            $user = $request->user();
            $seeker = $user->seeker;
            
            // CRITICAL FIX: Sync fields to User model for onboarding validation
            // OnboardingController checks User->date_of_birth and User->mobile, not resume fields
            $userFieldsToSync = false;
            
            if (isset($payload['date_of_birth'])) {
                $user->date_of_birth = $payload['date_of_birth'];
                $userFieldsToSync = true;
                \Log::info('Syncing date_of_birth to User model', [
                    'user_id' => $user->id,
                    'date_of_birth' => $payload['date_of_birth']
                ]);
            }
            
            // Sync phone to User->mobile (frontend sends 'phone', backend checks 'mobile')
            if (isset($payload['phone'])) {
                // Phone is stored as "code|number" format (e.g., "971|1234567")
                $user->mobile = $payload['phone'];
                $userFieldsToSync = true;
                \Log::info('Syncing phone to User model as mobile', [
                    'user_id' => $user->id,
                    'mobile' => $payload['phone']
                ]);
            }
            
            if ($userFieldsToSync) {
                $user->save();
            }
            
            if ($seeker) {
                // Update seeker fields from basic information payload
                if (isset($payload['first_name'])) {
                    $seeker->first_name = $payload['first_name'];
                }
                if (isset($payload['last_name'])) {
                    $seeker->last_name = $payload['last_name'];
                }
                if (isset($payload['profession'])) {
                    $seeker->profession = $payload['profession'];
                }
                if (isset($payload['headline'])) {
                    $seeker->headline = $payload['headline'];
                }
                if (isset($payload['date_of_birth'])) {
                    $seeker->date_of_birth = $payload['date_of_birth'];
                }
                if (isset($payload['current_location'])) {
                    $seeker->current_location = $payload['current_location'];
                }

                // Sync profile photo if present in payload
                if (isset($payload['profile_photo'])) {
                    $photoPath = $payload['profile_photo'];
                    // Remove /storage/ prefix if present for database storage
                    $cleanPath = preg_replace('#^/?(storage/)?#', '', $photoPath);
                    $seeker->profile_image_path = $cleanPath;
                    
                    // Update User model too for backward compatibility
                    $user->profile_photo_path = $cleanPath;
                    $userFieldsToSync = true;
                    
                    \Log::info('Synced profile photo to Seeker model', [
                        'user_id' => $user->id,
                        'seeker_id' => $seeker->id,
                        'path' => $cleanPath
                    ]);
                }
                
                // FORCE PROFILE COMPLETE TO TRUE
                $seeker->is_profile_complete = true;
                $seeker->save();
                
                \Log::info('Seeker profile marked as complete', [
                    'user_id' => $user->id,
                    'seeker_id' => $seeker->id,
                    'is_profile_complete' => $seeker->is_profile_complete
                ]);
            }
        }
        
        // Invalidate BOTH caches after update
        Cache::forget("resume_user_{$userId}");
        Cache::forget("resume_full_{$userId}");

        return response()->json([
            'message' => 'Section updated successfully.',
            'resume' => $resume,
            'profile_completion' => $resume->profile_completion,
        ]);
    }

    /**
     * Upload a document (profile photo, certificates, etc.) for the resume.
     * 
     * Stores files in the public disk under 'resume_uploads' directory.
     * Maximum file size: 2MB. Accepts PDF, JPEG, JPG, PNG formats.
     * 
     * @param Request $request The incoming HTTP request with file upload
     * @return JsonResponse Upload result with file path and public URL
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:2048|mimes:jpeg,jpg,png,pdf',
            'field_name' => 'nullable|string',
            'section' => 'nullable|string|in:work_experience,education,documents,basic_information',
            'entry_id' => 'nullable|integer', // For specific entries in arrays
        ]);

        $path = $request->file('file')->store('resume_uploads/docs', 'public');
        $publicUrl = Storage::url($path);
        
        // Invalidate BOTH caches as uploaded file may affect resume data
        $userId = $request->user()->id;
        Cache::forget("resume_user_{$userId}");
        Cache::forget("resume_full_{$userId}");

        return response()->json([
            'message' => 'File uploaded successfully.',
            'path' => $path,
            'url' => $publicUrl,
            'field_name' => $validated['field_name'] ?? null,
            'section' => $validated['section'] ?? null,
            'entry_id' => $validated['entry_id'] ?? null,
        ]);
    }

    /**
     * Update role-specific fields including file uploads for Smart Data Validation.
     * Handles the missing data modal submissions.
     */
    public function updateRoleSpecificFields(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'field_name' => 'required|string|in:full_body_photo,references_text,license_number,license_expiry,license_expiry_date,license_issuing_country,license_issuing_authority,license_type,vehicle_category,accident_free_years,has_clean_driving_record,driving_experience_years,driving_history,expected_location,height,weight,chest_measurement,date_of_birth,portfolio_link,specialization,safety_certifications,profession,personal_qualities,physical_capabilities,construction_projects,linkedin_url,website_url,availability_notes',
                'value' => 'nullable|string',
                'file' => 'nullable|file|max:2048|mimes:jpeg,jpg,png,pdf',
            ]);

            $resume = $this->firstOrCreateResume($request);
            $user = $request->user();
            $seeker = $user->seeker;
            $fieldName = $validated['field_name'];
            $value = $validated['value'];

            $driverLicense = $resume->driver_license;
            if (is_string($driverLicense)) {
                $driverLicense = json_decode($driverLicense, true) ?? [];
            } elseif (!is_array($driverLicense)) {
                $driverLicense = [];
            }

            $driverFieldNames = [
                'license_number',
                'license_expiry',
                'license_expiry_date',
                'license_issuing_country',
                'license_issuing_authority',
                'license_type',
                'accident_free_years',
                'has_clean_driving_record',
            ];

            $message = '';
            $seekerDirty = false;

            if ($fieldName === 'full_body_photo') {
                $request->validate([
                    'file' => 'required|file|max:2048|mimes:jpeg,jpg,png|dimensions:min_width=400,min_height=600',
                ], [
                    'file.dimensions' => 'Full body photo must be at least 400x600 pixels.',
                    'file.mimes' => 'Full body photo must be a JPEG or PNG image.',
                ]);

                $path = $request->file('file')->store('full_body_photos', 'public');
                $resume->full_body_photo = $path;
                $message = 'Full body photo uploaded successfully.';
            } elseif (in_array($fieldName, ['height', 'weight', 'chest_measurement', 'date_of_birth'], true)) {
                switch ($fieldName) {
                    case 'height':
                        $request->validate(['value' => 'required|numeric|between:100,250']);
                        $user->height = $value;
                        $message = 'Height updated successfully.';
                        break;
                    case 'weight':
                        $request->validate(['value' => 'required|numeric|between:30,200']);
                        $user->weight = $value;
                        $message = 'Weight updated successfully.';
                        break;
                    case 'chest_measurement':
                        $request->validate(['value' => 'required|numeric|between:50,150']);
                        $user->chest_measurement = $value;
                        $message = 'Chest measurement updated successfully.';
                        break;
                    case 'date_of_birth':
                        $request->validate(['value' => 'required|date|before:today']);
                        $user->date_of_birth = $value;
                        $message = 'Date of birth updated successfully.';
                        break;
                }

                $user->save();
            } else {
                switch ($fieldName) {
                    case 'references':
                        $resume->references = $value;
                        $message = 'References updated successfully.';
                        break;

                    case 'references_text':
                        $resume->references_text = $value;
                        $message = 'References updated successfully.';
                        break;

                    case 'license_number':
                        $request->validate(['value' => 'nullable|string|max:50']);
                        $normalizedLicenseNumber = $value !== '' ? $value : null;
                        $driverLicense['license_number'] = $normalizedLicenseNumber;
                        $resume->license_number = $normalizedLicenseNumber;
                        if ($seeker) {
                            $seeker->license_number = $normalizedLicenseNumber;
                            $seekerDirty = true;
                        }
                        $message = 'License number updated successfully.';
                        break;

                    case 'license_expiry':
                        $request->validate(['value' => 'nullable|date']);
                        $normalizedExpiry = $value !== '' ? $value : null;
                        $driverLicense['license_expiry'] = $normalizedExpiry;
                        $driverLicense['license_expiry_date'] = $normalizedExpiry;
                        $resume->license_expiry = $normalizedExpiry;
                        $resume->license_expiry_date = $normalizedExpiry;
                        if ($seeker) {
                            $seeker->license_expiry_date = $normalizedExpiry;
                            $seekerDirty = true;
                        }
                        $message = 'License expiry date updated successfully.';
                        break;

                    case 'license_expiry_date':
                        $request->validate(['value' => 'nullable|date']);
                        $normalizedExpiryDate = $value !== '' ? $value : null;
                        $driverLicense['license_expiry_date'] = $normalizedExpiryDate;
                        $resume->license_expiry_date = $normalizedExpiryDate;
                        if ($seeker) {
                            $seeker->license_expiry_date = $normalizedExpiryDate;
                            $seekerDirty = true;
                        }
                        $message = 'License expiry date updated successfully.';
                        break;

                    case 'license_issuing_country':
                        $request->validate(['value' => 'nullable|string|max:100']);
                        $normalizedCountry = $value !== '' ? $value : null;
                        $driverLicense['license_issuing_country'] = $normalizedCountry;
                        $resume->license_issuing_country = $normalizedCountry;
                        if ($seeker) {
                            $seeker->license_issuing_country = $normalizedCountry;
                            $seekerDirty = true;
                        }
                        $message = 'License issuing country updated successfully.';
                        break;

                    case 'license_issuing_authority':
                        $request->validate(['value' => 'nullable|string|max:150']);
                        $normalizedAuthority = $value !== '' ? $value : null;
                        $driverLicense['license_issuing_authority'] = $normalizedAuthority;
                        $resume->license_issuing_authority = $normalizedAuthority;
                        if ($seeker) {
                            $seeker->license_issuing_authority = $normalizedAuthority;
                            $seekerDirty = true;
                        }
                        $message = 'License issuing authority updated successfully.';
                        break;

                    case 'vehicle_category':
                        $request->validate(['value' => 'required|in:LTV,HTV']);
                        $resume->vehicle_category = $value;
                        $message = 'Vehicle category updated successfully.';
                        break;

                    case 'portfolio_link':
                        $request->validate(['value' => 'nullable|url']);
                        $resume->portfolio_link = $value;
                        $message = 'Portfolio link updated successfully.';
                        break;

                    case 'specialization':
                        $request->validate(['value' => 'required|string|max:100']);
                        $resume->specialization = $value;
                        $message = 'Specialization updated successfully.';
                        break;

                    case 'safety_certifications':
                        $resume->safety_certifications = $value;
                        $message = 'Safety certifications updated successfully.';
                        break;

                    case 'profession':
                        $request->validate(['value' => 'required|string|max:100']);
                        $resume->profession = $value;
                        $message = 'Profession updated successfully.';
                        break;

                    case 'license_type':
                        $request->validate(['value' => 'nullable|string|max:50']);
                        $normalizedType = $value !== '' ? $value : null;
                        $driverLicense['license_type'] = $normalizedType;
                        $resume->license_type = $normalizedType;
                        if ($seeker) {
                            $seeker->license_type = $normalizedType;
                            $seekerDirty = true;
                        }
                        $message = 'License type updated successfully.';
                        break;

                    case 'accident_free_years':
                        $request->validate(['value' => 'nullable|string|max:50']);
                        $normalizedYears = $value === '' || $value === null ? null : (int) $value;
                        $driverLicense['accident_free_years'] = $normalizedYears;
                        $resume->accident_free_years = $normalizedYears;
                        if ($seeker) {
                            $seeker->accident_free_years = $normalizedYears;
                            $seekerDirty = true;
                        }
                        $message = 'Accident free years updated successfully.';
                        break;

                    case 'has_clean_driving_record':
                        $request->validate(['value' => 'nullable|in:0,1,true,false']);
                        $parsedBool = in_array(strtolower((string) $value), ['1', 'true'], true);
                        $driverLicense['has_clean_driving_record'] = $parsedBool;
                        $resume->has_clean_driving_record = $parsedBool;
                        if ($seeker) {
                            $seeker->has_clean_driving_record = $parsedBool;
                            $seekerDirty = true;
                        }
                        $message = 'Clean driving record updated successfully.';
                        break;

                    case 'driving_experience_years':
                        $request->validate(['value' => 'required|integer|min:0|max:50']);
                        $resume->driving_experience_years = (int) $value;
                        $message = 'Driving experience updated successfully.';
                        break;

                    case 'driving_history':
                        $resume->driving_history = json_decode($value, true) ?? [$value];
                        $message = 'Driving history updated successfully.';
                        break;

                    case 'personal_qualities':
                        $qualities = json_decode($value, true);
                        if (!$qualities && $value) {
                            $qualities = array_map('trim', explode(',', $value));
                        }
                        $resume->personal_qualities = $qualities;
                        $message = 'Personal qualities updated successfully.';
                        break;

                    case 'physical_capabilities':
                        $capabilities = json_decode($value, true);
                        if (!$capabilities && $value) {
                            $capabilities = array_map('trim', explode(',', $value));
                        }
                        $resume->physical_capabilities = $capabilities;
                        $message = 'Physical capabilities updated successfully.';
                        break;

                    case 'construction_projects':
                        $projects = json_decode($value, true);
                        if (!$projects && $value) {
                            $projects = array_map('trim', explode("\n", $value));
                        }
                        $resume->construction_projects = $projects;
                        $message = 'Construction projects updated successfully.';
                        break;

                    case 'linkedin_url':
                        $request->validate(['value' => 'nullable|url']);
                        $resume->linkedin_url = $value;
                        $message = 'LinkedIn URL updated successfully.';
                        break;

                    case 'website_url':
                        $request->validate(['value' => 'nullable|url']);
                        $resume->website_url = $value;
                        $message = 'Website URL updated successfully.';
                        break;

                    case 'availability_notes':
                        $resume->availability_notes = $value;
                        $message = 'Availability notes updated successfully.';
                        break;

                    case 'expected_location':
                        $request->validate(['value' => 'nullable|string|max:255']);
                        $jobPreferences = $resume->job_preferences ?? [];
                        if (!is_array($jobPreferences)) {
                            $jobPreferences = [];
                        }
                        $jobPreferences['preferred_locations'] = $value;
                        $resume->job_preferences = $jobPreferences;
                        $message = 'Expected location updated successfully.';
                        break;

                    default:
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'field_name' => 'Invalid field name provided.',
                        ]);
                }

                if (in_array($fieldName, $driverFieldNames, true)) {
                    $resume->driver_license = $driverLicense;
                }

                if ($seekerDirty && $seeker) {
                    $seeker->save();
                }
            }

            $resume->save();
            $resume->calculateProfileCompletion();

            Cache::forget("resume_user_{$user->id}");
            Cache::forget("resume_full_{$user->id}");

            return response()->json([
                'success' => true,
                'message' => $message,
                'resume' => $resume->fresh(),
                'profile_completion' => $resume->profile_completion,
                'field_updated' => $fieldName,
                'driver_license' => $resume->driver_license,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error in updateRoleSpecificFields', [
                'message' => $e->getMessage(),
                'seeker_id' => $request->user()?->seeker?->id,
                'field_name' => $request->input('field_name'),
            ]);

            return response()->json([
                'message' => 'Error updating role-specific fields: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check for missing required fields based on CV role.
     * Used by the Smart Data Validation system.
     */
    public function checkMissingFields(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cv_role' => 'required|string|in:domestic_worker,driver,security_guard,beautician,steel_fixer',
            ]);

            $resume = $this->firstOrCreateResume($request);
            $cvRole = $validated['cv_role'];
            $user = $request->user();
            $seeker = $user->seeker;
            $missingFields = [];

            // Check basic information first (required for all roles)
            $basicInfo = $resume->basic_information ?? [];
            
            // Check for name
            if (empty($basicInfo['full_name']) && empty($basicInfo['first_name'])) {
                $missingFields[] = [
                    'field' => 'full_name',
                    'label' => 'Full Name',
                    'type' => 'text',
                    'required' => true,
                    'message' => 'Your full name is required for CV generation.',
                ];
            }

            // Check for contact info
            if (empty($basicInfo['email'])) {
                $missingFields[] = [
                    'field' => 'email',
                    'label' => 'Email Address',
                    'type' => 'email',
                    'required' => true,
                    'message' => 'Email address is required for CV generation.',
                ];
            }

            if (empty($basicInfo['phone'])) {
                $missingFields[] = [
                    'field' => 'phone',
                    'label' => 'Phone Number',
                    'type' => 'text',
                    'required' => true,
                    'message' => 'Phone number is required for CV generation.',
                ];
            }

        switch ($cvRole) {
            case 'domestic_worker':
                if (empty($resume->full_body_photo)) {
                    $missingFields[] = [
                        'field' => 'full_body_photo',
                        'label' => 'Full Body Photo',
                        'type' => 'file',
                        'required' => true,
                        'message' => 'A full body picture is required for Domestic Worker applications.',
                    ];
                }
                if (empty($resume->references_text) && empty($resume->references)) {
                    $missingFields[] = [
                        'field' => 'references_text',
                        'label' => 'References',
                        'type' => 'textarea',
                        'required' => true,
                        'message' => 'References are required for Domestic Worker applications.',
                    ];
                }
                if (empty($resume->personal_qualities)) {
                    $missingFields[] = [
                        'field' => 'personal_qualities',
                        'label' => 'Personal Qualities',
                        'type' => 'textarea',
                        'required' => false,
                        'message' => 'Describe your personal qualities (e.g., honest, punctual, hardworking).',
                    ];
                }
                break;

            case 'driver':
                $licenseNumber = $seeker?->license_number ?? $resume->license_number;
                $licenseExpiryDate = $seeker?->license_expiry_date ?? $resume->license_expiry_date ?? $resume->license_expiry;
                $licenseType = $seeker?->license_type ?? $resume->license_type;
                $jobPreferences = $resume->job_preferences ?? [];
                $expectedLocation = is_array($jobPreferences)
                    ? ($jobPreferences['preferred_locations'] ?? $jobPreferences['preferred_location'] ?? null)
                    : null;

                if (empty($licenseNumber)) {
                    $missingFields[] = [
                        'field' => 'license_number',
                        'label' => 'License Number',
                        'type' => 'text',
                        'required' => true,
                        'message' => 'License number is required for Driver applications.',
                    ];
                }
                if (empty($licenseExpiryDate)) {
                    $missingFields[] = [
                        'field' => 'license_expiry_date',
                        'label' => 'License Expiry Date',
                        'type' => 'date',
                        'required' => true,
                        'message' => 'License expiry date is required for Driver applications.',
                    ];
                }
                if (empty($licenseType)) {
                    $missingFields[] = [
                        'field' => 'license_type',
                        'label' => 'License Type',
                        'type' => 'text',
                        'required' => true,
                        'message' => 'License type is required for Driver applications.',
                    ];
                }

                if (empty($expectedLocation) || (is_string($expectedLocation) && trim($expectedLocation) === '')) {
                    $missingFields[] = [
                        'field' => 'expected_location',
                        'label' => 'Expected Location',
                        'type' => 'text',
                        'required' => true,
                        'message' => 'Expected location is required for Driver applications.',
                    ];
                }
                break;

            case 'security_guard':
                // Check height
                if (empty($user->height)) {
                    $missingFields[] = [
                        'field' => 'height',
                        'label' => 'Height (feet)',
                        'type' => 'number',
                        'required' => true,
                        'message' => 'Height is required for Security Guard applications.',
                    ];
                }
                
                // Check weight
                if (empty($user->weight)) {
                    $missingFields[] = [
                        'field' => 'weight',
                        'label' => 'Weight (kg)',
                        'type' => 'number',
                        'required' => true,
                        'message' => 'Weight is required for Security Guard applications.',
                    ];
                }
                
                // Check chest measurement
                if (empty($user->chest_measurement)) {
                    $missingFields[] = [
                        'field' => 'chest_measurement',
                        'label' => 'Chest Measurement (inches)',
                        'type' => 'number',
                        'required' => true,
                        'message' => 'Chest measurement is required for Security Guard applications.',
                    ];
                }
                
                // Check date of birth
                if (empty($user->date_of_birth)) {
                    $missingFields[] = [
                        'field' => 'date_of_birth',
                        'label' => 'Date of Birth',
                        'type' => 'date',
                        'required' => true,
                        'message' => 'Date of birth is required for Security Guard applications.',
                    ];
                }
                break;

            case 'beautician':
                if (empty($resume->specialization)) {
                    $missingFields[] = [
                        'field' => 'specialization',
                        'label' => 'Specialization',
                        'type' => 'text',
                        'required' => true,
                        'message' => 'Specialization is required for Beautician applications (e.g., Hair, Makeup, Nails).',
                    ];
                }
                if (empty($resume->portfolio_link)) {
                    $missingFields[] = [
                        'field' => 'portfolio_link',
                        'label' => 'Portfolio Link',
                        'type' => 'url',
                        'required' => false,
                        'message' => 'Portfolio link is recommended for Beautician applications.',
                    ];
                }
                break;

            case 'steel_fixer':
                if (empty($resume->safety_certifications)) {
                    $missingFields[] = [
                        'field' => 'safety_certifications',
                        'label' => 'Safety Certifications',
                        'type' => 'textarea',
                        'required' => true,
                        'message' => 'Safety certifications are required for Steel Fixer applications.',
                    ];
                }
                if (empty($resume->physical_capabilities)) {
                    $missingFields[] = [
                        'field' => 'physical_capabilities',
                        'label' => 'Physical Capabilities',
                        'type' => 'textarea',
                        'required' => false,
                        'message' => 'Describe your physical capabilities (e.g., heavy lifting, height work).',
                    ];
                }
                if (empty($resume->construction_projects)) {
                    $missingFields[] = [
                        'field' => 'construction_projects',
                        'label' => 'Construction Projects',
                        'type' => 'textarea',
                        'required' => false,
                        'message' => 'List your notable construction projects.',
                    ];
                }
                // Check physical stats for steel fixer too
                if (empty($user->height)) {
                    $missingFields[] = [
                        'field' => 'height',
                        'label' => 'Height (cm)',
                        'type' => 'number',
                        'required' => false,
                        'message' => 'Height is recommended for Steel Fixer applications.',
                    ];
                }
                if (empty($user->weight)) {
                    $missingFields[] = [
                        'field' => 'weight',
                        'label' => 'Weight (kg)',
                        'type' => 'number',
                        'required' => false,
                        'message' => 'Weight is recommended for Steel Fixer applications.',
                    ];
                }
                break;
        }

        return response()->json([
            'has_missing_fields' => !empty($missingFields),
            'missing_fields' => $missingFields,
            'cv_role' => $cvRole,
        ]);
        
        } catch (\Exception $e) {
            \Log::error('Error in checkMissingFields: ' . $e->getMessage());
            \Log::error('Request data: ' . json_encode($request->all()));
            
            return response()->json([
                'message' => 'Error checking missing fields: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract and validate section payload from request.
     * 
     * @param Request $request The incoming HTTP request
     * @return array The validated payload data
     * @throws ValidationException If payload is empty or not an array
     */
    private function extractSectionPayload(Request $request): array
    {
        $payload = $request->input();

        // Allow empty payloads for initialization
        if (!is_array($payload) || empty($payload)) {
            return [];
        }

        return $payload;
    }

    /**
     * Normalize section key from URL format to database column format.
     * 
     * Converts kebab-case to snake_case (e.g., 'basic-information' -> 'basic_information').
     * 
     * @param string $section The section key in URL format
     * @return string The normalized section key for database
     */
    private function normalizeSectionKey(string $section): string
    {
        return str_replace('-', '_', strtolower($section));
    }

    /**
     * Get or create a resume for the authenticated user.
     * 
     * Creates a new resume with default empty JSON columns if none exists.
     * Also links the seeker_id if available but not yet set.
     * 
     * @param Request $request The incoming HTTP request
     * @return SeekerResume The user's resume model
     */
    private function firstOrCreateResume(Request $request): SeekerResume
    {
        $user = $request->user();
        $defaults = array_fill_keys(self::JSON_COLUMNS, []);
        $defaults['profile_completion'] = 0;
        $defaults['seeker_id'] = optional($user->seeker)->id;

        $resume = SeekerResume::firstOrCreate(
            ['user_id' => $user->id],
            $defaults
        );

        if (!$resume->seeker_id && $user->seeker) {
            $resume->seeker_id = $user->seeker->id;
            $resume->save();
        }

        // Calculate profile completion if it's 0 (newly created or not calculated)
        if ($resume->profile_completion === 0) {
            $resume->calculateProfileCompletion();
        }

        return $resume;
    }
    
    /**
     * Clear the resume cache for a specific user.
     * 
     * @param int $userId The user ID whose cache should be cleared
     * @return void
     */
    public static function clearResumeCache(int $userId): void
    {
        Cache::forget("resume_user_{$userId}");
    }

    /**
     * Validate section-specific data based on enhanced requirements.
     * 
     * @param string $sectionKey The section key (e.g., 'work_experience')
     * @param array $payload The data payload to validate
     * @param Request $request The current request instance
     * @throws ValidationException If validation fails
     */
    private function validateSectionData(string $sectionKey, array $payload, Request $request): void
    {
        switch ($sectionKey) {
            case 'basic_information':
                // Get current user info for email validation
                $userId = $request->user()->id;
                $currentEmail = $request->user()->email;
                
                // Build validation rules dynamically
                $rules = [
                    'profile_photo' => 'nullable|string|max:500',
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'date_of_birth' => 'required|date|before:today',
                    'father_name' => 'nullable|string|max:255',
                    'phone' => 'required|string|max:50',
                    'emergency_contact_name' => 'required|string|max:255',
                    'emergency_contact_phone' => 'required|string|max:50',
                    'country' => 'required|string|max:255',
                    'gender' => 'nullable|string|in:male,female,other',
                    'marital_status' => 'nullable|string|max:100',
                    'nationality' => 'nullable|string|max:255',
                    'religion' => 'nullable|string|max:100',
                ];
                
                // Add email validation with unique rule
                if (isset($payload['email'])) {
                    $email = trim($payload['email']);
                    // Only validate uniqueness if email is changing
                    if (strtolower($email) !== strtolower($currentEmail)) {
                        $rules['email'] = [
                            'required',
                            'email',
                            'max:255',
                            Rule::unique('users', 'email')->ignore($userId),
                        ];
                    } else {
                        $rules['email'] = 'required|email|max:255';
                    }
                } else {
                    $rules['email'] = 'required|email|max:255';
                }
                
                $messages = [
                    'first_name.required' => 'First name is required.',
                    'last_name.required' => 'Last name is required.',
                    'date_of_birth.required' => 'Date of birth is required.',
                    'date_of_birth.before' => 'Date of birth must be before today.',
                    'phone.required' => 'Phone number is required.',
                    'email.required' => 'Email address is required.',
                    'email.email' => 'Email must be a valid email address.',
                    'email.unique' => 'Email already exists.',
                    'emergency_contact_name.required' => 'Emergency contact name is required.',
                    'emergency_contact_phone.required' => 'Emergency contact phone is required.',
                    'country.required' => 'Country is required.',
                ];
                
                $request->validate($rules, $messages);
                break;

            case 'work_experience':
                if (isset($payload['work_experience']) && is_array($payload['work_experience'])) {
                    foreach ($payload['work_experience'] as $index => $experience) {
                        $request->validate([
                            "work_experience.{$index}.title" => 'required|string|max:255',
                            "work_experience.{$index}.company" => 'required|string|max:255',
                            "work_experience.{$index}.location" => 'nullable|string|max:255',
                            "work_experience.{$index}.start_date" => 'required|date',
                            "work_experience.{$index}.end_date" => 'nullable|date|after_or_equal:start_date',
                            "work_experience.{$index}.current" => 'boolean',
                            "work_experience.{$index}.description" => 'nullable|string|max:2000',
                            "work_experience.{$index}.document_path" => 'nullable|string|max:500',
                        ], [
                            "work_experience.{$index}.document_path.string" => 'Document path must be a valid string.',
                            "work_experience.{$index}.document_path.max" => 'Document path is too long.',
                        ]);
                    }
                }
                break;

            case 'education':
                if (isset($payload['education']) && is_array($payload['education'])) {
                    foreach ($payload['education'] as $index => $education) {
                        $request->validate([
                            "education.{$index}.degree" => 'required|string|max:255',
                            "education.{$index}.institution" => 'required|string|max:255',
                            "education.{$index}.field_of_study" => 'nullable|string|max:255',
                            "education.{$index}.start_date" => 'required|date',
                            "education.{$index}.end_date" => 'nullable|date|after_or_equal:start_date',
                            "education.{$index}.grade" => 'nullable|string|max:100',
                            "education.{$index}.description" => 'nullable|string|max:2000',
                            "education.{$index}.document_path" => 'nullable|string|max:500',
                        ], [
                            "education.{$index}.document_path.string" => 'Document path must be a valid string.',
                            "education.{$index}.document_path.max" => 'Document path is too long.',
                        ]);
                    }
                }
                break;

            case 'certifications':
                if (isset($payload['certifications']) && is_array($payload['certifications'])) {
                    foreach ($payload['certifications'] as $index => $certification) {
                        $request->validate([
                            "certifications.{$index}.certification_name" => 'required|string|max:255',
                            "certifications.{$index}.issuer" => 'required|string|max:255',
                            "certifications.{$index}.issue_date" => 'required|date',
                            "certifications.{$index}.expiry_date" => 'nullable|date|after_or_equal:issue_date',
                            "certifications.{$index}.does_not_expire" => 'boolean',
                            "certifications.{$index}.credential_id" => 'nullable|string|max:100',
                            "certifications.{$index}.credential_url" => 'nullable|url|max:500',
                        ], [
                            "certifications.{$index}.certification_name.required" => 'Certification name is required.',
                            "certifications.{$index}.issuer.required" => 'Certificate issuer is required.',
                        ]);
                    }
                }
                break;

            case 'references':
                if (isset($payload['references']) && is_array($payload['references'])) {
                    foreach ($payload['references'] as $index => $reference) {
                        $request->validate([
                            "references.{$index}.name" => 'required|string|max:255',
                            "references.{$index}.job_title" => 'nullable|string|max:255',
                            "references.{$index}.company_name" => 'nullable|string|max:255',
                            "references.{$index}.email" => 'nullable|email|max:255',
                            "references.{$index}.phone" => 'nullable|string|max:50',
                            "references.{$index}.relationship" => 'nullable|string|max:100',
                        ], [
                            "references.{$index}.email.email" => 'Email must be a valid email address.',
                            "references.{$index}.company_name.max" => 'Company name is too long.',
                            "references.{$index}.relationship.max" => 'Relationship description is too long.',
                        ]);
                    }
                }
                break;

            case 'skills':
                // Handle new dual-list format (must_have, nice_to_have) or legacy format
                if (isset($payload['must_have']) && is_array($payload['must_have'])) {
                    // Validate new dual-list format
                    foreach ($payload['must_have'] as $index => $skill) {
                        $request->validate([
                            "must_have.{$index}" => 'required|string|max:100',
                        ], [
                            "must_have.{$index}.required" => 'Must-have skill name is required.',
                        ]);
                        
                        // Use firstOrCreate to ensure skill exists in database
                        \App\Models\Skill::firstOrCreate([
                            'name' => trim($skill),
                            'category' => 'User Defined',
                            'is_active' => true,
                            'usage_count' => \DB::raw('usage_count + 1')
                        ]);
                    }
                }
                
                if (isset($payload['nice_to_have']) && is_array($payload['nice_to_have'])) {
                    // Validate new dual-list format
                    foreach ($payload['nice_to_have'] as $index => $skill) {
                        $request->validate([
                            "nice_to_have.{$index}" => 'required|string|max:100',
                        ], [
                            "nice_to_have.{$index}.required" => 'Nice-to-have skill name is required.',
                        ]);
                        
                        // Use firstOrCreate to ensure skill exists in database
                        \App\Models\Skill::firstOrCreate([
                            'name' => trim($skill),
                            'category' => 'User Defined',
                            'is_active' => true,
                            'usage_count' => \DB::raw('usage_count + 1')
                        ]);
                    }
                }
                
                // Legacy format support (backward compatibility)
                if (isset($payload['skills']) && is_array($payload['skills'])) {
                    foreach ($payload['skills'] as $index => $skill) {
                        $request->validate([
                            "skills.{$index}.skill" => 'required|string|max:100',
                            "skills.{$index}.proficiency" => 'nullable|string|in:Beginner,Intermediate,Advanced,Expert',
                            "skills.{$index}.category" => 'nullable|string|max:100',
                        ], [
                            "skills.{$index}.skill.required" => 'Skill name is required.',
                            "skills.{$index}.proficiency.in" => 'Proficiency must be one of: Beginner, Intermediate, Advanced, Expert',
                        ]);
                        
                        // Use updateOrCreate to ensure skill exists in database
                        if (isset($skill['skill'])) {
                            $skillModel = \App\Models\Skill::updateOrCreate([
                                'name' => trim($skill['skill'])
                            ], [
                                'category' => $skill['category'] ?? 'User Defined',
                                'is_active' => true,
                            ]);
                            
                            // Increment usage count separately
                            $skillModel->increment('usage_count');
                        }
                    }
                }
                break;

            case 'professional_summary':
                if (isset($payload['professional_summary'])) {
                    $request->validate([
                        'professional_summary.career_objective' => 'nullable|string|max:3000',
                        'professional_summary.strengths' => 'nullable|array',
                        'professional_summary.industry_experience' => 'nullable|array',
                    ], [
                        'professional_summary.career_objective.max' => 'Professional summary must be less than 3000 characters.',
                    ]);
                }
                break;
        }
    }

    /**
     * Get driver details for the current seeker.
     * Used for Just-in-Time data collection when generating Driver CV template.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Driver details data
     */
    public function getDriverDetails(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;
        $resume = $this->firstOrCreateResume($request);
        
        // Combine data from both Seeker model and Resume driver_license JSON
        $driverLicense = $resume->driver_license ?? [];
        
        // Debug: Log what we have in database
        \Log::info('getDriverDetails - Seeker data:', [
            'license_number' => $seeker?->license_number,
            'license_type' => $seeker?->license_type,
            'license_expiry_date' => $seeker?->license_expiry_date,
            'license_issuing_country' => $seeker?->license_issuing_country,
            'license_issuing_authority' => $seeker?->license_issuing_authority,
            'accident_free_years' => $seeker?->accident_free_years,
            'has_clean_driving_record' => $seeker?->has_clean_driving_record,
        ]);
        
        \Log::info('getDriverDetails - Resume driver_license JSON:', $driverLicense);
        
        $driverDetails = [
            'license_number' => $seeker?->license_number ?? $driverLicense['license_number'] ?? null,
            'license_type' => $seeker?->license_type ?? $driverLicense['license_type'] ?? null,
            'license_expiry_date' => $seeker?->license_expiry_date?->format('Y-m-d') ?? $driverLicense['license_expiry_date'] ?? null,
            'license_issuing_country' => $seeker?->license_issuing_country ?? $driverLicense['license_issuing_country'] ?? null,
            'license_issuing_authority' => $seeker?->license_issuing_authority ?? $driverLicense['license_issuing_authority'] ?? null,
            'accident_free_years' => $seeker?->accident_free_years ?? $driverLicense['accident_free_years'] ?? null,
            'has_clean_driving_record' => $seeker?->has_clean_driving_record ?? $driverLicense['has_clean_driving_record'] ?? false,
        ];

        // Debug: Log final response
        \Log::info('getDriverDetails - Final response:', $driverDetails);
        
        return response()->json([
            'success' => true,
            'driver_details' => $driverDetails,
        ]);
    }

    /**
     * Update driver details for the current seeker.
     * Used for Just-in-Time data collection when generating Driver CV template.
     * Saves to both Seeker model and Resume driver_license JSON for consistency.
     * 
     * @param Request $request The incoming HTTP request with driver details
     * @return JsonResponse Updated driver details
     */
    public function updateDriverDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_number' => 'nullable|string|max:50',
            'license_type' => 'nullable|string|max:50',
            'license_expiry_date' => 'nullable|date',
            'license_issuing_country' => 'nullable|string|max:100',
            'license_issuing_authority' => 'nullable|string|max:150',
            'accident_free_years' => 'nullable|integer|min:0|max:50',
            'has_clean_driving_record' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $seeker = $user->seeker;
        $resume = $this->firstOrCreateResume($request);
        $userId = $user->id;

        $normalized = [];
        foreach ([
            'license_number',
            'license_type',
            'license_expiry_date',
            'license_issuing_country',
            'license_issuing_authority',
            'accident_free_years',
            'has_clean_driving_record',
        ] as $key) {
            if (!array_key_exists($key, $validated)) {
                continue;
            }

            $value = $validated[$key];
            if (is_string($value) && trim($value) == '') {
                $value = null;
            }
            $normalized[$key] = $value;
        }

        // Update Seeker model
        if ($seeker) {
            if (array_key_exists('license_number', $normalized)) {
                $seeker->license_number = $normalized['license_number'];
            }
            if (array_key_exists('license_type', $normalized)) {
                $seeker->license_type = $normalized['license_type'];
            }
            if (array_key_exists('license_expiry_date', $normalized)) {
                $seeker->license_expiry_date = $normalized['license_expiry_date'];
            }
            if (array_key_exists('license_issuing_country', $normalized)) {
                $seeker->license_issuing_country = $normalized['license_issuing_country'];
            }
            if (array_key_exists('license_issuing_authority', $normalized)) {
                $seeker->license_issuing_authority = $normalized['license_issuing_authority'];
            }
            if (array_key_exists('accident_free_years', $normalized)) {
                $seeker->accident_free_years = $normalized['accident_free_years'];
            }
            if (array_key_exists('has_clean_driving_record', $normalized)) {
                $seeker->has_clean_driving_record = (bool) $normalized['has_clean_driving_record'];
            }
            $seeker->save();
        }

        // Update Resume driver_license JSON
        $driverLicense = $resume->driver_license;
        if (is_string($driverLicense)) {
            $driverLicense = json_decode($driverLicense, true) ?? [];
        } elseif (!is_array($driverLicense)) {
            $driverLicense = [];
        }
        
        if (array_key_exists('license_number', $normalized)) {
            $driverLicense['license_number'] = $normalized['license_number'];
        }
        if (array_key_exists('license_type', $normalized)) {
            $driverLicense['license_type'] = $normalized['license_type'];
        }
        if (array_key_exists('license_expiry_date', $normalized)) {
            $driverLicense['license_expiry_date'] = $normalized['license_expiry_date'];
        }
        if (array_key_exists('license_issuing_country', $normalized)) {
            $driverLicense['license_issuing_country'] = $normalized['license_issuing_country'];
        }
        if (array_key_exists('license_issuing_authority', $normalized)) {
            $driverLicense['license_issuing_authority'] = $normalized['license_issuing_authority'];
        }
        if (array_key_exists('accident_free_years', $normalized)) {
            $driverLicense['accident_free_years'] = $normalized['accident_free_years'];
        }
        if (array_key_exists('has_clean_driving_record', $normalized)) {
            $driverLicense['has_clean_driving_record'] = (bool) $normalized['has_clean_driving_record'];
        }
        $resume->driver_license = $driverLicense;
        $resume->save();

        // Invalidate caches
        Cache::forget("resume_user_{$userId}");
        Cache::forget("resume_full_{$userId}");

        \Log::info('Driver details updated via Just-in-Time modal', [
            'user_id' => $userId,
            'seeker_id' => $seeker?->id,
            'license_number' => $seeker?->license_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Driver details saved successfully.',
            'driver_details' => $this->getDriverDetails($request)->getData(true)['driver_details'] ?? $normalized,
        ]);
    }

    /**
     * Get domestic worker details for the current seeker.
     * Used for Just-in-Time data collection when generating Domestic Worker CV template.
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Domestic worker details data
     */
    public function getDomesticWorkerDetails(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;
        $resume = $this->firstOrCreateResume($request);
        
        // Get basic info for height/weight
        $basicInfo = $resume->basic_information ?? [];
        
        $domesticWorkerDetails = [
            'number_of_children' => $seeker?->number_of_children ?? null,
            'skill_washing' => (bool) ($seeker?->skill_washing ?? false),
            'skill_cooking' => (bool) ($seeker?->skill_cooking ?? false),
            'skill_babysitting' => (bool) ($seeker?->skill_babysitting ?? false),
            'skill_cleaning' => (bool) ($seeker?->skill_cleaning ?? false),
            'full_body_image_path' => $seeker?->full_body_image_path ?? null,
            'full_body_image_url' => $seeker?->full_body_image_path 
                ? Storage::url($seeker->full_body_image_path)
                : null,
            // Additional fields from basic info
            'height' => $user->height ?? $basicInfo['height'] ?? null,
            'weight' => $user->weight ?? $basicInfo['weight'] ?? null,
            'religion' => $basicInfo['religion'] ?? null,
            'marital_status' => $basicInfo['marital_status'] ?? null,
            'date_of_birth' => $user->date_of_birth?->format('Y-m-d') ?? $basicInfo['date_of_birth'] ?? null,
            'nationality' => $basicInfo['nationality'] ?? null,
            'place_of_birth' => $basicInfo['place_of_birth'] ?? null,
        ];

        return response()->json([
            'success' => true,
            'domestic_worker_details' => $domesticWorkerDetails,
        ]);
    }

    /**
     * Update domestic worker details for the current seeker.
     * Used for Just-in-Time data collection when generating Domestic Worker CV template.
     * Saves to Seeker model and handles file upload for full body image.
     * 
     * @param Request $request The incoming HTTP request with domestic worker details
     * @return JsonResponse Updated domestic worker details
     */
    public function updateDomesticWorkerDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number_of_children' => 'nullable|integer|min:0|max:20',
            'skill_washing' => 'nullable|boolean',
            'skill_cooking' => 'nullable|boolean',
            'skill_babysitting' => 'nullable|boolean',
            'skill_cleaning' => 'nullable|boolean',
            'full_body_image' => 'nullable|file|max:2048|mimes:jpeg,jpg,png',
            'height' => 'nullable|numeric|between:100,250',
            'weight' => 'nullable|numeric|between:30,200',
        ], [
            'number_of_children.integer' => 'Number of children must be a valid number.',
            'number_of_children.max' => 'Number of children cannot exceed 20.',
            'full_body_image.max' => 'Full body image must be less than 2MB.',
            'full_body_image.mimes' => 'Full body image must be a JPEG or PNG file.',
        ]);

        $user = $request->user();
        $seeker = $user->seeker;
        $userId = $user->id;

        if (!$seeker) {
            return response()->json([
                'success' => false,
                'message' => 'Seeker profile not found.',
            ], 404);
        }

        // Update seeker fields
        if (isset($validated['number_of_children'])) {
            $seeker->number_of_children = $validated['number_of_children'];
        }
        
        $seeker->skill_washing = $validated['skill_washing'] ?? false;
        $seeker->skill_cooking = $validated['skill_cooking'] ?? false;
        $seeker->skill_babysitting = $validated['skill_babysitting'] ?? false;
        $seeker->skill_cleaning = $validated['skill_cleaning'] ?? false;

        // Handle full body image upload
        if ($request->hasFile('full_body_image')) {
            $file = $request->file('full_body_image');
            $path = $file->store('full_body_photos', 'public');
            $seeker->full_body_image_path = $path;
        }

        $seeker->save();

        // Update height/weight on user model if provided
        if (isset($validated['height'])) {
            $user->height = $validated['height'];
        }
        if (isset($validated['weight'])) {
            $user->weight = $validated['weight'];
        }
        if ($user->isDirty()) {
            $user->save();
        }

        // Invalidate caches
        Cache::forget("resume_user_{$userId}");
        Cache::forget("resume_full_{$userId}");

        \Log::info('Domestic worker details updated via Just-in-Time modal', [
            'user_id' => $userId,
            'seeker_id' => $seeker->id,
            'skills' => [
                'washing' => $seeker->skill_washing,
                'cooking' => $seeker->skill_cooking,
                'babysitting' => $seeker->skill_babysitting,
                'cleaning' => $seeker->skill_cleaning,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Domestic worker details saved successfully.',
            'domestic_worker_details' => [
                'number_of_children' => $seeker->number_of_children,
                'skill_washing' => $seeker->skill_washing,
                'skill_cooking' => $seeker->skill_cooking,
                'skill_babysitting' => $seeker->skill_babysitting,
                'skill_cleaning' => $seeker->skill_cleaning,
                'full_body_image_url' => $seeker->full_body_image_path 
                    ? Storage::url($seeker->full_body_image_path)
                    : null,
            ],
        ]);
    }

    /**
     * Get security guard details for the current seeker
     * Used for Just-in-Time data collection when generating Security Guard CV template
     * 
     * @param Request $request The incoming HTTP request
     * @return JsonResponse Security guard details data
     */
    public function getSecurityGuardDetails(Request $request): JsonResponse
    {
        $user = $request->user();
        $seeker = $user->seeker;
        $resume = $seeker->resume;
        
        // Get security guard details from JSON field in resume
        $securityGuardDetails = $resume->security_guard_details ?? [];
        
        // Debug: Log what we have in database
        \Log::info('getSecurityGuardDetails - Resume security_guard_details JSON:', $securityGuardDetails);
        
        $details = [
            'height' => $securityGuardDetails['height'] ?? null,
            'weight' => $securityGuardDetails['weight'] ?? null,
            'chest_size' => $securityGuardDetails['chest_size'] ?? null,
            'full_body_image_path' => $securityGuardDetails['full_body_image_path'] ?? null,
            'full_body_image_url' => $securityGuardDetails['full_body_image_path'] 
                ? Storage::url($securityGuardDetails['full_body_image_path'])
                : null,
        ];

        // Debug: Log final response
        \Log::info('getSecurityGuardDetails - Final response:', $details);
        
        return response()->json([
            'success' => true,
            'security_guard_details' => $details,
        ]);
    }

    /**
     * Update security guard details for the current seeker
     * Used for Just-in-Time data collection when generating Security Guard CV template
     * 
     * @param Request $request The incoming HTTP request with security guard details
     * @return JsonResponse Updated security guard details
     */
    public function updateSecurityGuardDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'height' => 'nullable|numeric|min:100|max:250',
            'weight' => 'nullable|numeric|min:30|max:200',
            'chest_size' => 'nullable|numeric', // Removed min/max validation as requested
            'full_body_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        $user = $request->user();
        $seeker = $user->seeker;
        $resume = $seeker->resume;
        $userId = $user->id;

        DB::beginTransaction();
        try {
            // Handle full body image upload
            if ($request->hasFile('full_body_image')) {
                $file = $request->file('full_body_image');
                $filename = 'security_guard_full_body_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('security_guard_images', $filename, 'public');
                
                // Delete old full body image if exists
                if ($seeker->full_body_image_path) {
                    Storage::disk('public')->delete($seeker->full_body_image_path);
                }
                
                $seeker->full_body_image_path = $path;
                $seeker->save();
            }

            // Store all security guard details in JSON field
            $securityGuardDetails = $resume->security_guard_details ?? [];
            
            if (isset($validated['height'])) {
                $securityGuardDetails['height'] = $validated['height'];
            }
            if (isset($validated['weight'])) {
                $securityGuardDetails['weight'] = $validated['weight'];
            }
            if (isset($validated['chest_size'])) {
                $securityGuardDetails['chest_size'] = $validated['chest_size'];
            }
            
            // Also store full body image path in JSON for easy access
            if ($seeker->full_body_image_path) {
                $securityGuardDetails['full_body_image_path'] = $seeker->full_body_image_path;
            }
            
            $resume->security_guard_details = $securityGuardDetails;
            $resume->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating security guard details', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Invalidate caches
        Cache::forget("resume_user_{$userId}");
        Cache::forget("resume_full_{$userId}");

        \Log::info('Security guard details updated via Just-in-Time modal', [
            'user_id' => $userId,
            'seeker_id' => $seeker->id,
            'height' => $seeker->height,
            'weight' => $seeker->weight,
            'chest_size' => $resume->security_guard_details['chest_size'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Security guard details saved successfully.',
            'security_guard_details' => [
                'height' => $seeker->height,
                'weight' => $seeker->weight,
                'chest_size' => $resume->security_guard_details['chest_size'] ?? null,
                'full_body_image_url' => $seeker->full_body_image_path 
                    ? Storage::url($seeker->full_body_image_path)
                    : null,
            ],
        ]);
    }

    /**
     * Get CV preview data for a seeker (for employer viewing).
     * Returns JSON data for frontend rendering.
     * 
     * @param Request $request
     * @param int $seekerId
     * @return JsonResponse
     */
    public function preview(Request $request, int $seekerId): JsonResponse
    {
        // Find the seeker
        $seeker = Seeker::with(['user', 'resume'])->find($seekerId);
        
        if (!$seeker) {
            return response()->json(['message' => 'Seeker not found'], 404);
        }

        $resume = $seeker->resume;
        if (!$resume) {
            return response()->json(['message' => 'Resume not found'], 404);
        }

        // Convert resume to array and append URLs
        $resumeData = $resume->toArray();
        $resumeData = $this->appendUrlsToResumeData($resumeData);

        // Merge seeker and user data
        $resumeData = array_merge($resumeData, [
            'seeker_id' => $seeker->id,
            'first_name' => $seeker->first_name,
            'last_name' => $seeker->last_name,
            'headline' => $seeker->headline,
            'job_title' => $seeker->job_title,
            'current_location' => $seeker->current_location,
            'profile_image_path' => $seeker->profile_image_path,
            'profile_image_url' => $seeker->profile_image_path 
                ? Storage::url($seeker->profile_image_path)
                : null,
            'number_of_children' => $seeker->number_of_children,
            'skill_washing' => (bool) $seeker->skill_washing,
            'skill_cooking' => (bool) $seeker->skill_cooking,
            'skill_babysitting' => (bool) $seeker->skill_babysitting,
            'skill_cleaning' => (bool) $seeker->skill_cleaning,
            'full_body_image_path' => $seeker->full_body_image_path,
            'full_body_image_url' => $seeker->full_body_image_path 
                ? Storage::url($seeker->full_body_image_path)
                : null,
            'license_number' => $seeker->license_number,
            'license_type' => $seeker->license_type,
            'license_expiry_date' => $seeker->license_expiry_date,
            'license_issuing_country' => $seeker->license_issuing_country,
            'height' => $seeker->user->height,
            'weight' => $seeker->user->weight,
            'date_of_birth' => $seeker->user->date_of_birth,
        ]);

        return response()->json([
            'resume' => $resumeData,
            'profile_completion' => $resume->profile_completion,
        ]);
    }

    /**
     * Render CV as HTML for browser preview (debugging/quick view).
     * Opens in a new tab as rendered HTML - useful for layout debugging.
     * 
     * @param Request $request
     * @param int $seekerId
     * @return \Illuminate\View\View
     */
    public function previewHtml(Request $request, int $seekerId)
    {
        $seeker = Seeker::with(['user', 'resume'])->findOrFail($seekerId);
        $resume = $seeker->resume;
        
        if (!$resume) {
            abort(404, 'Resume not found for this seeker.');
        }

        $template = $request->query('template', 'general');
        $locale = $request->query('locale', 'en');

        // Validate template
        $validTemplates = ['general', 'driver', 'domestic', 'security', 'bilingual', 'standard'];
        if (!in_array($template, $validTemplates)) {
            $template = 'general';
        }

        // Prepare resume data
        $resumeData = $this->prepareResumeDataForPdf($seeker, $resume);

        // Get view name
        $viewName = $this->getTemplateView($template, $locale);

        return view($viewName, [
            'resumeData' => $resumeData,
            'seeker' => $seeker,
            'resume' => $resume,
            'template' => $template,
            'locale' => $locale,
        ]);
    }

    /**
     * Get ALL seeker fields needed for CV templates.
     * This is the SINGLE SOURCE OF TRUTH for seeker data in resume responses.
     * Fixes the "Zombie Bug" where data exists in DB but disappears on reload.
     */
    private function getSeekerFieldsForResume(Seeker $seeker, ?User $user): array
    {
        return [
            // Identity
            'seeker_id' => $seeker->id,
            
            // Personal Information
            'first_name' => $seeker->first_name ?? '',
            'last_name' => $seeker->last_name ?? '',
            'profession' => $seeker->profession ?? '',
            'headline' => $seeker->headline ?? '',
            'bio' => $seeker->bio ?? '',
            'current_location' => $seeker->current_location ?? '',
            'date_of_birth' => $user?->date_of_birth,
            
            // Profile Image
            'profile_image_path' => $seeker->profile_image_path,
            'profile_image_url' => $seeker->profile_image_path 
                ? Storage::url($seeker->profile_image_path)
                : null,
            
            // Full Body Image (for Domestic Worker / Security Guard)
            'full_body_image_path' => $seeker->full_body_image_path,
            'full_body_image_url' => $seeker->full_body_image_path 
                ? Storage::url($seeker->full_body_image_path)
                : null,
            
            // Physical Attributes (from User model)
            'height' => $user?->height,
            'weight' => $user?->weight,
            'chest_size' => $user?->chest_measurement ?? $user?->chest_size,
            
            // ============================================
            // DRIVER-SPECIFIC FIELDS
            // ============================================
            'license_number' => $seeker->license_number ?? '',
            'license_type' => $seeker->license_type ?? '',
            'license_expiry_date' => $seeker->license_expiry_date?->format('Y-m-d'),
            'license_issuing_country' => $seeker->license_issuing_country ?? '',
            'license_issuing_authority' => $seeker->license_issuing_authority ?? '',
            'accident_free_years' => (int) ($seeker->accident_free_years ?? 0),
            'has_clean_driving_record' => (bool) $seeker->has_clean_driving_record,
            
            // ============================================
            // DOMESTIC WORKER FIELDS - CRITICAL: Cast to boolean!
            // DB stores 1/0, frontend expects true/false
            // ============================================
            'number_of_children' => (int) ($seeker->number_of_children ?? 0),
            'skill_washing' => (bool) $seeker->skill_washing,
            'skill_cooking' => (bool) $seeker->skill_cooking,
            'skill_babysitting' => (bool) $seeker->skill_babysitting,
            'skill_cleaning' => (bool) $seeker->skill_cleaning,
            
            // ============================================
            // SEEKER OBJECT (for templates that access user.seeker.*)
            // ============================================
            'seeker' => [
                'id' => $seeker->id,
                'first_name' => $seeker->first_name ?? '',
                'last_name' => $seeker->last_name ?? '',
                'full_name' => trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? '')),
                'profession' => $seeker->profession ?? '',
                'headline' => $seeker->headline ?? '',
                'date_of_birth' => $seeker->date_of_birth?->format('Y-m-d'),
                'current_location' => $seeker->current_location ?? '',
                'profile_image_path' => $seeker->profile_image_path,
                'profile_image_url' => $seeker->profile_image_path 
                    ? Storage::url($seeker->profile_image_path)
                    : null,
                'full_body_image_path' => $seeker->full_body_image_path,
                'full_body_image_url' => $seeker->full_body_image_path 
                    ? Storage::url($seeker->full_body_image_path)
                    : null,
                // Driver fields
                'license_number' => $seeker->license_number ?? '',
                'license_type' => $seeker->license_type ?? '',
                'license_expiry_date' => $seeker->license_expiry_date?->format('Y-m-d'),
                'license_issuing_country' => $seeker->license_issuing_country ?? '',
                'license_issuing_authority' => $seeker->license_issuing_authority ?? '',
                'accident_free_years' => (int) ($seeker->accident_free_years ?? 0),
                'has_clean_driving_record' => (bool) $seeker->has_clean_driving_record,
                // Domestic worker fields
                'number_of_children' => (int) ($seeker->number_of_children ?? 0),
                'skill_washing' => (bool) $seeker->skill_washing,
                'skill_cooking' => (bool) $seeker->skill_cooking,
                'skill_babysitting' => (bool) $seeker->skill_babysitting,
                'skill_cleaning' => (bool) $seeker->skill_cleaning,
                // Physical
                'height' => $user?->height,
                'weight' => $user?->weight,
                'chest_size' => $user?->chest_measurement ?? $user?->chest_size,
            ],
        ];
    }

    /**
     * Prepare resume data for HTML preview rendering.
     * NOTE: Server-side PDF generation (Browsershot) has been REMOVED.
     * PDF generation is now handled client-side via @react-pdf/renderer.
     */
    public function prepareResumeDataForPdf(Seeker $seeker, SeekerResume $resume): array
    {
        $resumeData = $resume->toArray();
        $resumeData = $this->appendUrlsToResumeData($resumeData);
        $user = $seeker->user;

        // Merge all seeker fields
        $resumeData = array_merge($resumeData, $this->getSeekerFieldsForResume($seeker, $user));

        // Add security guard details from JSON field
        $securityGuardDetails = $resume->security_guard_details ?? [];
        if (!empty($securityGuardDetails)) {
            $resumeData['security_guard_details'] = $securityGuardDetails;
            // Also add individual fields at root level for easy access in template
            $resumeData['height'] = $securityGuardDetails['height'] ?? $resumeData['height'];
            $resumeData['weight'] = $securityGuardDetails['weight'] ?? $resumeData['weight'];
            $resumeData['chest_size'] = $securityGuardDetails['chest_size'] ?? $resumeData['chest_size'];
        }

        return $resumeData;
    }

    /**
     * Get the current seeker's resume data with user info.
     * Used by frontend to load complete profile data for editing.
     */
    public function getSeekerResumeFull(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $seeker = $user->seeker;
            
            if (!$seeker) {
                return response()->json([
                    'user' => $user,
                    'resume' => [],
                    'profile_completion' => 0,
                    'onboarding' => ['is_completed' => false]
                ]);
            }

            $resume = $seeker->resume;
            
            if (!$resume) {
                return response()->json([
                    'user' => $user,
                    'resume' => [],
                    'profile_completion' => 0,
                    'onboarding' => ['is_completed' => false]
                ]);
            }

            // Append URLs to resume data
            $resumeData = $this->appendUrlsToResumeData($resume->toArray());
            
            // Calculate profile completion
            $profileCompletion = $this->calculateProfileCompletion($resumeData);
            
            // Get onboarding status
            $onboarding = [
                'is_completed' => $user->is_onboarding_completed ?? false,
                'is_profile_complete' => $seeker->is_profile_complete ?? false,
                'can_complete' => ($seeker->is_profile_complete ?? false) && !($user->is_onboarding_completed ?? false)
            ];
            
            return response()->json([
                'user' => $user,
                'resume' => $resumeData,
                'profile_completion' => $profileCompletion,
                'onboarding' => $onboarding
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to get seeker resume full', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'user' => $request->user(),
                'resume' => [],
                'profile_completion' => 0,
                'onboarding' => ['is_completed' => false]
            ], 500);
        }
    }

    /**
     * Get the current seeker's resume data.
     * Used by frontend to load resume data for editing and preview.
     */
    public function getSeekerResume(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $seeker = $user->seeker;
            
            if (!$seeker) {
                return response()->json([
                    'error' => 'Seeker not found',
                    'message' => 'This user has not created a seeker profile yet.'
                ], 404);
            }
            
            $seeker->increment('profile_views');
            
            // Get resume data
            $resume = $seeker->resume;
            if (!$resume) {
                return response()->json([
                    'error' => 'Resume not found',
                    'message' => 'This seeker has not created a resume yet.'
                ], 404);
            }
            
            // Convert resume to array and append URLs
            $resumeData = $resume->toArray();
            $resumeData = $this->appendUrlsToResumeData($resumeData);
            
            // CRITICAL: Preserve resume-specific fields before merging seeker fields
            $resumeLanguages = $resumeData['languages'] ?? null;
            $resumePrimaryLanguage = $resumeData['primary_language'] ?? null;
            
            // Merge ALL seeker fields into resume data - CRITICAL for CV templates
            $resumeData = array_merge($resumeData, $this->getSeekerFieldsForResume($seeker, $user));
            
            // Restore resume-specific fields that might be overwritten
            if ($resumeLanguages !== null) {
                $resumeData['languages'] = $resumeLanguages;
                \Log::info('Languages restored from resume', [
                    'seeker_id' => $seeker->id,
                    'languages' => $resumeLanguages,
                    'languages_type' => gettype($resumeLanguages),
                    'languages_is_array' => is_array($resumeLanguages)
                ]);
            }
            if ($resumePrimaryLanguage !== null) {
                $resumeData['primary_language'] = $resumePrimaryLanguage;
                \Log::info('Primary language restored from resume', [
                    'seeker_id' => $seeker->id,
                    'primary_language' => $resumePrimaryLanguage
                ]);
            }
            
            $resumeData['profile_views'] = $seeker->profile_views;

            return response()->json([
                'success' => true,
                'resume' => $resumeData,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get seeker resume', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to get seeker resume',
                'message' => 'Please try again.'
            ], 500);
        }
    }

    /**
     * Get the appropriate view template based on template type and locale.
     * Used for HTML preview only - PDF generation is client-side.
     */
    private function getTemplateView(string $template, string $locale): string
    {
        $templateMap = [
            'general' => 'templates.resume.general',
            'driver' => 'templates.resume.driver',
            'domestic' => 'pdf.domestic',
            'security' => 'pdf.security',
            'bilingual' => 'pdf.bilingual',
            'standard' => 'pdf.standard',
        ];

        $template = strtolower($template);
        
        if (isset($templateMap[$template])) {
            return $templateMap[$template];
        }
        
        return $templateMap['general'];
    }

    /**
     * Calculate profile completion percentage.
     */
    private function calculateProfileCompletion(array $resumeData): int
    {
        $sections = [
            'basic_information' => 20,
            'professional_summary' => 15,
            'work_experience' => 20,
            'education' => 15,
            'skills' => 15,
            'languages' => 10,
            'documents' => 5,
        ];

        $completion = 0;
        $totalWeight = array_sum($sections);

        foreach ($sections as $section => $weight) {
            if (!empty($resumeData[$section])) {
                $completion += $weight;
            }
        }

        return (int) round(($completion / $totalWeight) * 100);
    }
}
