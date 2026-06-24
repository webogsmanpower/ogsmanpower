<?php

namespace App\Http\Resources;

use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SeekerResource
 * 
 * Transforms Seeker model data for API responses.
 * 
 * STRICT CONTRACT:
 * - All fields have explicit types (int, string, bool, array)
 * - Nullable fields are explicitly marked
 * - Uses ImageUploadService for URL resolution
 * - Provides both raw paths (for frontend) and full URLs (for server-side)
 * 
 * @package App\Http\Resources
 */
class SeekerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imageService = app(ImageUploadService::class);

        return [
            // Identity
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            
            // Personal Information - strings with defaults
            'first_name' => (string) ($this->first_name ?? ''),
            'last_name' => (string) ($this->last_name ?? ''),
            'full_name' => $this->resolveFullName(),
            'profession' => (string) ($this->profession ?? ''),
            'headline' => (string) ($this->headline ?? ''),
            'bio' => (string) ($this->bio ?? ''),
            'current_location' => (string) ($this->current_location ?? ''),
            
            // Additional personal info for CV templates
            'nationality' => (string) ($this->nationality ?? ''),
            'religion' => (string) ($this->religion ?? ''),
            'marital_status' => (string) ($this->marital_status ?? ''),
            'place_of_birth' => (string) ($this->place_of_birth ?? ''),
            'city' => (string) ($this->city ?? ''),
            'country' => (string) ($this->country ?? ''),
            'address' => (string) ($this->address ?? ''),
            'phone' => (string) ($this->phone ?? ''),
            'passport_number' => (string) ($this->passport_number ?? ''),
            
            // Dates - nullable
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            
            // Physical attributes (for Security Guard / Domestic Worker templates)
            'height' => $this->height !== null ? (int) $this->height : null,
            'weight' => $this->weight !== null ? (int) $this->weight : null,
            'chest_size' => $this->chest_size !== null ? (int) $this->chest_size : null,
            
            // Numeric fields - always integers
            'experience_years' => (int) ($this->experience_years ?? 0),
            'profile_completion' => (int) ($this->profile_completion ?? 0),
            'profile_views' => (int) ($this->profile_views ?? 0),
            
            // Boolean flags
            'is_profile_complete' => (bool) ($this->profile_completion >= 80),
            
            // Arrays
            'skills' => $this->skills ?? [],
            
            // Image paths - raw for frontend URL building
            'profile_image_path' => $imageService->getRawPath($this->profile_image_path),
            'full_body_image_path' => $imageService->getRawPath($this->full_body_image_path),
            'resume_path' => $imageService->getRawPath($this->resume_path),
            
            // Image URLs - full URLs for immediate use
            'profile_image_url' => $imageService->getAvatarUrl($this->profile_image_path),
            'full_body_image_url' => $this->full_body_image_path 
                ? $imageService->getPublicUrl($this->full_body_image_path) 
                : null,
            'resume_url' => $this->resume_path 
                ? $imageService->getPublicUrl($this->resume_path) 
                : null,
            
            // ============================================
            // DRIVER-SPECIFIC FIELDS (explicit whitelist)
            // ============================================
            'license_number' => (string) ($this->license_number ?? ''),
            'license_expiry_date' => $this->license_expiry_date?->format('Y-m-d'),
            'license_issuing_country' => (string) ($this->license_issuing_country ?? ''),
            'license_issuing_authority' => (string) ($this->license_issuing_authority ?? ''),
            'license_type' => (string) ($this->license_type ?? ''),
            'accident_free_years' => (int) ($this->accident_free_years ?? 0),
            'has_clean_driving_record' => (bool) $this->has_clean_driving_record,
            
            // ============================================
            // DOMESTIC WORKER FIELDS (explicit whitelist with BOOLEAN casting)
            // ============================================
            'number_of_children' => (int) ($this->number_of_children ?? 0),
            // CRITICAL: Cast to boolean - DB stores 1/0, frontend expects true/false
            'skill_washing' => $this->castToBool($this->skill_washing),
            'skill_cooking' => $this->castToBool($this->skill_cooking),
            'skill_babysitting' => $this->castToBool($this->skill_babysitting),
            'skill_cleaning' => $this->castToBool($this->skill_cleaning),
            
            // ============================================
            // SECURITY GUARD FIELDS (explicit whitelist)
            // ============================================
            'sira_license_number' => (string) ($this->sira_license_number ?? ''),
            'sira_expiry_date' => $this->sira_expiry_date?->format('Y-m-d'),
            
            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Related resources (conditionally loaded)
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn() => new UserResource($this->user)
            ),
            'resume' => $this->when(
                $this->relationLoaded('resume') && $this->resume,
                fn() => new ResumeResource($this->resume)
            ),
            'latest_application' => $this->when(
                $this->relationLoaded('applications') && $this->applications->isNotEmpty(),
                fn() => new JobApplicationResource($this->applications->first())
            ),
        ];
    }

    /**
     * Cast value to boolean - handles int (1/0), string ("1"/"0"), and bool.
     * This fixes the "Zombie Bug" where DB stores 1 but frontend checks true.
     */
    private function castToBool($value): bool
    {
        if ($value === null) return false;
        if (is_bool($value)) return $value;
        if (is_int($value)) return $value === 1;
        if (is_string($value)) return $value === '1' || strtolower($value) === 'true';
        return (bool) $value;
    }

    /**
     * Resolve full name - NEVER returns empty string.
     */
    private function resolveFullName(): string
    {
        $firstName = trim($this->first_name ?? '');
        $lastName = trim($this->last_name ?? '');

        if ($firstName || $lastName) {
            return trim("{$firstName} {$lastName}");
        }

        // Fallback to user name or email
        if ($this->relationLoaded('user') && $this->user) {
            if (!empty($this->user->name)) {
                return $this->user->name;
            }
            if (!empty($this->user->email)) {
                return explode('@', $this->user->email)[0];
            }
        }

        return 'User';
    }
}
