<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SeekerResume Model
 * 
 * Canonical model for storing complete "Edit Resume" page data for job seekers.
 * Consolidates all 13 accordion sections into a single queryable structure.
 * 
 * Design reference: /mnt/data/132d327e-26c0-4f16-8c8f-d30743a86ef3.png
 * 
 * Enhanced JSON Structure Documentation:
 * 
 * work_experience: Array of objects with enhanced fields:
 *   [{title, company, location, start_date, end_date, current, description, achievements, document_path}]
 *   - document_path: Optional file path for offer letters, relieving letters, etc.
 * 
 * education: Array of objects with enhanced fields:
 *   [{degree, institution, field_of_study, start_date, end_date, grade, description, document_path}]
 *   - document_path: Optional file path for degree certificates, transcripts, etc.
 * 
 * certifications: Array of objects with renamed and enhanced fields:
 *   [{certification_name, issuer, issue_date, expiry_date, does_not_expire, credential_id, credential_url}]
 *   - certification_name: Renamed from 'title' or 'name'
 *   - issuer: Renamed from 'authority' or 'issuing_organization'
 *   - does_not_expire: Boolean field to disable expiry date requirement
 * 
 * references: Array of objects with enhanced fields:
 *   [{name, job_title, company_name, email, phone, relationship}]
 *   - job_title: Reference person's job title
 *   - company_name: Reference person's company
 *   - email: Reference person's email address
 *   - relationship: Relationship to applicant (e.g., Manager, Colleague)
 * 
 * skills: Array of objects with simplified structure:
 *   [{skill, proficiency, category}]
 *   - Removed description field for cleaner tag-based interface
 * 
 * professional_summary: Object with career focus:
 *   {career_objective, strengths, industry_experience}
 *   - Removed short_description field for streamlined interface
 * 
 * @property int $id
 * @property int $user_id
 * @property int|null $seeker_id
 * @property int $profile_completion
 * @property string|null $primary_language
 * @property bool $is_rtl
 * @property string|null $resume_format
 * @property array|null $basic_information
 * @property array|null $documents
 * @property array|null $social_profiles
 * @property array|null $professional_summary
 * @property array|null $work_experience
 * @property array|null $education
 * @property array|null $skills
 * @property array|null $languages
 * @property array|null $certifications
 * @property array|null $references
 * @property array|null $job_preferences
 * @property array|null $availability
 * @property array|null $privacy_settings
 * @property array|null $generated_cv
 * @property array|null $resume_versions
 * @property array|null $extra
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class SeekerResume extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seeker_resumes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'seeker_id',
        'profile_completion',
        'primary_language',
        'is_rtl',
        'resume_format',
        'profession', // NEW: User's profession/job title
        'basic_information',
        'documents',
        'social_profiles',
        'linkedin_url', // NEW: LinkedIn URL for CV display
        'website_url', // NEW: Personal website URL
        'professional_summary',
        'work_experience',
        'education',
        'skills',
        'languages',
        'certifications',
        'references',
        'job_preferences',
        'availability',
        'availability_notes', // NEW: Additional availability text
        'privacy_settings',
        'generated_cv',
        'resume_versions',
        'extra',
        // Role-specific fields for Smart Data Validation
        'full_body_photo',
        'personal_qualities', // NEW: Domestic worker qualities
        'references_text',
        'driver_license',
        'license_number',
        'license_expiry',
        'license_expiry_date',
        'license_issuing_country',
        'license_issuing_authority',
        'vehicle_category',
        'license_type', // NEW: License type (LTV, HTV, etc.)
        'accident_free_years',
        'has_clean_driving_record',
        'driving_experience_years', // NEW: Years of driving experience
        'driving_history', // NEW: Driving history JSON
        'height',
        'weight',
        'chest_measurement',
        'portfolio_link',
        'specialization',
        'safety_certifications',
        'physical_capabilities', // NEW: Steel fixer capabilities
        'construction_projects', // NEW: Steel fixer projects
        // Translation cache
        'translations',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'profile_completion' => 'integer',
        'is_rtl' => 'boolean',
        'basic_information' => 'array',
        'documents' => 'array',
        'social_profiles' => 'array',
        'professional_summary' => 'array',
        'work_experience' => 'array',
        'education' => 'array',
        'skills' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'references' => 'array',
        'job_preferences' => 'array',
        'availability' => 'array',
        'privacy_settings' => 'array',
        'generated_cv' => 'array',
        'resume_versions' => 'array',
        'extra' => 'array',
        'driver_license' => 'array',
        'security_guard_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        // Role-specific fields casting
        'license_expiry' => 'date',
        'license_expiry_date' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'chest_measurement' => 'decimal:2',
        'driving_experience_years' => 'integer',
        'has_clean_driving_record' => 'boolean',
        // NEW: JSON array fields for CV generation
        'personal_qualities' => 'array',
        'driving_history' => 'array',
        'physical_capabilities' => 'array',
        'construction_projects' => 'array',
        // Translation cache
        'translations' => 'array',
    ];

    /**
     * Get the user that owns the resume.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the seeker that owns the resume.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Get the latest version from resume_versions array.
     * 
     * Returns the most recent version entry or null if no versions exist.
     * 
     * @return array|null
     */
    public function getLatestVersion(): ?array
    {
        if (empty($this->resume_versions) || !is_array($this->resume_versions)) {
            return null;
        }

        // Assuming versions are stored with 'created_at' timestamp
        // Sort by created_at descending and return the first one
        $versions = $this->resume_versions;
        usort($versions, function ($a, $b) {
            $timeA = $a['created_at'] ?? 0;
            $timeB = $b['created_at'] ?? 0;
            return $timeB <=> $timeA;
        });

        return $versions[0] ?? null;
    }

    /**
     * Convert resume to public-safe array.
     * 
     * Filters out sensitive information based on privacy_settings
     * and returns only publicly visible data.
     * 
     * @return array
     */
    public function toPublicArray(): array
    {
        $privacySettings = $this->privacy_settings ?? [];
        
        // Base public data
        $publicData = [
            'id' => $this->id,
            'profile_completion' => $this->profile_completion,
            'primary_language' => $this->primary_language,
            'is_rtl' => $this->is_rtl,
        ];

        // Conditionally include sections based on privacy settings
        // Default to visible if privacy_settings not configured
        
        if ($this->isFieldVisible('basic_information', $privacySettings)) {
            // Filter sensitive fields from basic_information
            $basicInfo = $this->basic_information ?? [];
            $publicData['basic_information'] = [
                'full_name' => $basicInfo['full_name'] ?? null,
                'profile_photo' => $basicInfo['profile_photo'] ?? null,
                'country' => $basicInfo['country'] ?? null,
                'city' => $basicInfo['city'] ?? null,
                // Exclude: phone, whatsapp, email, emergency_contact, full address
            ];
        }

        if ($this->isFieldVisible('professional_summary', $privacySettings)) {
            $publicData['professional_summary'] = $this->professional_summary;
        }

        if ($this->isFieldVisible('work_experience', $privacySettings)) {
            $publicData['work_experience'] = $this->work_experience;
        }

        if ($this->isFieldVisible('education', $privacySettings)) {
            $publicData['education'] = $this->education;
        }

        if ($this->isFieldVisible('skills', $privacySettings)) {
            $publicData['skills'] = $this->skills;
        }

        if ($this->isFieldVisible('languages', $privacySettings)) {
            $publicData['languages'] = $this->languages;
        }

        if ($this->isFieldVisible('certifications', $privacySettings)) {
            $publicData['certifications'] = $this->certifications;
        }

        // References typically private by default
        if ($this->isFieldVisible('references', $privacySettings, false)) {
            $publicData['references'] = $this->references;
        }

        if ($this->isFieldVisible('job_preferences', $privacySettings)) {
            $publicData['job_preferences'] = $this->job_preferences;
        }

        if ($this->isFieldVisible('availability', $privacySettings)) {
            $publicData['availability'] = $this->availability;
        }

        return $publicData;
    }

    /**
     * Check if a field is visible based on privacy settings.
     *
     * @param string $field
     * @param array $privacySettings
     * @param bool $defaultVisible
     * @return bool
     */
    protected function isFieldVisible(string $field, array $privacySettings, bool $defaultVisible = true): bool
    {
        // If privacy setting exists for this field, use it; otherwise use default
        return $privacySettings[$field . '_visibility'] ?? $defaultVisible;
    }

    /**
     * Calculate and update profile completion percentage.
     * 
     * Analyzes all sections and calculates completion based on filled fields.
     * Updates the profile_completion field.
     *
     * @return int The calculated completion percentage
     */
    public function calculateProfileCompletion(): int
    {
        $sections = [
            'basic_information' => 15,      // 15% weight
            'documents' => 5,               // 5% weight
            'social_profiles' => 5,         // 5% weight
            'professional_summary' => 10,   // 10% weight
            'work_experience' => 20,        // 20% weight
            'education' => 15,              // 15% weight
            'skills' => 10,                 // 10% weight
            'languages' => 5,               // 5% weight
            'certifications' => 5,          // 5% weight
            'references' => 5,              // 5% weight
            'job_preferences' => 5,         // 5% weight
        ];

        $totalCompletion = 0;

        foreach ($sections as $section => $weight) {
            $data = $this->$section;
            if (!empty($data) && is_array($data)) {
                // Section has data, add its weight
                $totalCompletion += $weight;
            }
        }

        $this->profile_completion = $totalCompletion;
        $this->save();

        return $totalCompletion;
    }

    /**
     * Create a new version snapshot of the current resume.
     *
     * @param string|null $note Optional note describing the version
     * @return void
     */
    public function createVersionSnapshot(?string $note = null): void
    {
        $versions = $this->resume_versions ?? [];
        
        $newVersion = [
            'snapshot_id' => uniqid('version_', true),
            'created_at' => now()->toISOString(),
            'note' => $note,
            'data' => [
                'basic_information' => $this->basic_information,
                'professional_summary' => $this->professional_summary,
                'work_experience' => $this->work_experience,
                'education' => $this->education,
                'skills' => $this->skills,
                'languages' => $this->languages,
                'certifications' => $this->certifications,
                'references' => $this->references,
                'job_preferences' => $this->job_preferences,
                'availability' => $this->availability,
            ],
        ];

        $versions[] = $newVersion;
        
        // Keep only last 10 versions to prevent excessive data growth
        if (count($versions) > 10) {
            $versions = array_slice($versions, -10);
        }

        $this->resume_versions = $versions;
        $this->save();
    }
}
