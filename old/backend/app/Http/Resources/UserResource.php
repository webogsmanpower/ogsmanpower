<?php

namespace App\Http\Resources;

use App\Services\ImageUploadService;
use App\Services\SeekerProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource
 * 
 * Transforms User model data for API responses.
 * 
 * STRICT CONTRACT:
 * - All fields have explicit types (int, string, bool)
 * - Critical UI fields NEVER return null (use sensible defaults)
 * - Uses Services for business logic (URL resolution, name building)
 * - No raw model data - everything is transformed
 * 
 * @package App\Http\Resources
 */
class UserResource extends JsonResource
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
            // Identity - always present
            'id' => (int) $this->id,
            'email' => (string) ($this->email ?? ''),
            'role' => (string) ($this->role ?? 'seeker'),
            'mobile' => (string) ($this->mobile ?? ''),
            'credit_balance' => (float) ($this->credit_balance ?? 0),
            
            // Status flags - always boolean
            'is_onboarding_completed' => (bool) $this->is_onboarding_completed,
            'is_profile_complete' => $this->resolveIsProfileComplete(),
            
            // Roles for RBAC
            'roles' => $this->when($this->relationLoaded('roles'), function() {
                return $this->roles->pluck('name')->toArray();
            }),
            
            // Display fields - NEVER null for critical UI
            'display_name' => $this->resolveDisplayName(),
            'avatar_url' => $this->resolveAvatarUrl($imageService),
            'profile_completion' => $this->resolveProfileCompletion(),
            'profile_views' => $this->resolveProfileViews(),
            
            // Timestamps
            'last_login' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            
            // Nested resources (only when loaded)
            'seeker' => $this->when(
                $this->relationLoaded('seeker') && $this->seeker,
                fn() => new SeekerResource($this->seeker)
            ),
            'employer' => $this->when(
                $this->relationLoaded('employer') && $this->employer,
                fn() => new EmployerResource($this->employer)
            ),
        ];
    }

    /**
     * Resolve display name - NEVER returns empty string.
     */
    private function resolveDisplayName(): string
    {
        // Seeker: use first + last name
        if ($this->role === 'seeker' && $this->seeker) {
            $firstName = trim($this->seeker->first_name ?? '');
            $lastName = trim($this->seeker->last_name ?? '');
            if ($firstName || $lastName) {
                return trim("{$firstName} {$lastName}");
            }
        }

        // Employer: use company name
        if ($this->role === 'employer' && $this->employer) {
            if (!empty($this->employer->company_name)) {
                return $this->employer->company_name;
            }
        }

        // Fallback chain: user name > email prefix > 'User'
        if (!empty($this->name)) {
            return $this->name;
        }

        if (!empty($this->email)) {
            return explode('@', $this->email)[0];
        }

        return 'User';
    }

    /**
     * Resolve avatar URL using ImageUploadService.
     * Returns raw path for frontend to build URL - NEVER null.
     */
    private function resolveAvatarUrl(ImageUploadService $imageService): string
    {
        // Employer: use company logo
        if ($this->role === 'employer' && $this->employer) {
            return $this->employer->company_logo_path ?? 'images/mock/company-logo.svg';
        }

        // Seeker: use profile image with fallback to resume photo
        if ($this->role === 'seeker' && $this->seeker) {
            $path = $this->seeker->profile_image_path;
            
            // Try resume photo as fallback (only if resume relation is loaded)
            if (empty($path) && $this->seeker->relationLoaded('resume') && $this->seeker->resume) {
                $path = $this->seeker->resume->basic_information['profile_photo'] ?? null;
            }

            return $path ?? 'images/mock/seeker-avatar.svg';
        }

        // Default avatar
        return 'images/mock/seeker-avatar.svg';
    }

    /**
     * Resolve profile completion percentage.
     */
    private function resolveProfileCompletion(): int
    {
        if ($this->role === 'seeker' && $this->seeker) {
            return (int) ($this->seeker->profile_completion ?? 0);
        }

        // Employers are considered 100% complete
        return 100;
    }

    /**
     * Resolve profile views count.
     */
    private function resolveProfileViews(): int
    {
        if ($this->role === 'seeker' && $this->seeker) {
            return (int) ($this->seeker->profile_views ?? 0);
        }

        return 0;
    }

    /**
     * Resolve is_profile_complete flag.
     */
    private function resolveIsProfileComplete(): bool
    {
        if ($this->role === 'seeker' && $this->seeker) {
            return (bool) ($this->seeker->is_profile_complete ?? false);
        }

        // Employers are considered complete
        return true;
    }
}
