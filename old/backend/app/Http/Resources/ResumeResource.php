<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * ResumeResource
 * 
 * Transforms SeekerResume model data for API responses.
 * Handles URL resolution for file paths and ensures consistent output.
 * 
 * @package App\Http\Resources
 */
class ResumeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseUrl = config('app.url');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'seeker_id' => $this->seeker_id,
            'profile_completion' => $this->profile_completion,
            'primary_language' => $this->primary_language,
            'is_rtl' => (bool) $this->is_rtl,
            'resume_format' => $this->resume_format,
            'profession' => $this->profession,
            
            // JSON sections with URL resolution
            'basic_information' => $this->transformBasicInformation($baseUrl),
            'documents' => $this->transformDocuments($baseUrl),
            'social_profiles' => $this->social_profiles,
            'professional_summary' => $this->professional_summary,
            'work_experience' => $this->work_experience,
            'education' => $this->education,
            'skills' => $this->skills,
            'languages' => $this->languages,
            'certifications' => $this->certifications,
            'references' => $this->references,
            'job_preferences' => $this->job_preferences,
            'availability' => $this->availability,
            'availability_notes' => $this->availability_notes,
            'privacy_settings' => $this->privacy_settings,
            
            // Role-specific fields
            'full_body_photo' => $this->resolveUrl($this->full_body_photo, $baseUrl) 
                ?? ($this->seeker?->full_body_image_path ? $this->resolveUrl($this->seeker->full_body_image_path, $baseUrl) : null),
            'personal_qualities' => $this->personal_qualities,
            'references_text' => $this->references_text,
            'license_number' => $this->license_number,
            'license_expiry' => $this->license_expiry?->format('Y-m-d'),
            'license_expiry_date' => $this->license_expiry_date?->format('Y-m-d'),
            'license_issuing_country' => $this->license_issuing_country,
            'license_issuing_authority' => $this->license_issuing_authority,
            'vehicle_category' => $this->vehicle_category,
            'license_type' => $this->license_type,
            'accident_free_years' => $this->accident_free_years,
            'has_clean_driving_record' => (bool) $this->has_clean_driving_record,
            'driving_experience_years' => $this->driving_experience_years,
            'driving_history' => $this->driving_history,
            'height' => $this->height,
            'weight' => $this->weight,
            'chest_measurement' => $this->chest_measurement,
            'portfolio_link' => $this->portfolio_link,
            'specialization' => $this->specialization,
            'safety_certifications' => $this->safety_certifications,
            'physical_capabilities' => $this->physical_capabilities,
            'construction_projects' => $this->construction_projects,
            'linkedin_url' => $this->linkedin_url,
            'website_url' => $this->website_url,
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transform basic information with resolved URLs.
     *
     * @param string $baseUrl
     * @return array|null
     */
    private function transformBasicInformation(string $baseUrl): ?array
    {
        $basicInfo = $this->basic_information;
        
        if (!$basicInfo || !is_array($basicInfo)) {
            return $basicInfo;
        }

        // Resolve profile photo URL
        if (isset($basicInfo['profile_photo']) && $basicInfo['profile_photo']) {
            $basicInfo['profile_photo'] = $this->resolveUrl($basicInfo['profile_photo'], $baseUrl);
        }

        return $basicInfo;
    }

    /**
     * Transform documents array with resolved URLs.
     *
     * @param string $baseUrl
     * @return array|null
     */
    private function transformDocuments(string $baseUrl): ?array
    {
        $documents = $this->documents;
        
        if (!$documents || !is_array($documents)) {
            return $documents;
        }

        foreach ($documents as $key => $document) {
            if (isset($document['file_path']) && $document['file_path']) {
                $documents[$key]['file_url'] = $this->resolveUrl($document['file_path'], $baseUrl);
            }
        }

        return $documents;
    }

    /**
     * Resolve a file path to a full URL.
     *
     * @param string|null $path
     * @param string $baseUrl
     * @return string|null
     */
    private function resolveUrl(?string $path, string $baseUrl): ?string
    {
        if (!$path) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // If Storage::url() already returns a full URL, use that directly
        $storageUrl = Storage::disk('public')->url($path);
        if (str_starts_with($storageUrl, 'http://') || str_starts_with($storageUrl, 'https://')) {
            return $storageUrl;
        }

        // Otherwise, prepend base URL
        return $baseUrl . $storageUrl;
    }
}
