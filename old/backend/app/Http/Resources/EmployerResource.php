<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * EmployerResource
 * 
 * API Resource for Employer model.
 */
class EmployerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $this->company_name,
            'company_name_ar' => $this->company_name_ar,
            'trade_license_number' => $this->trade_license_number,
            'company_type' => $this->company_type,
            'industry' => $this->industry,
            'company_size' => $this->company_size,
            'country' => $this->country,
            'city' => $this->city,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo_path' => $this->getRawPath($this->logo_path),
            'logo_url' => $this->getFullUrl($this->logo_path),
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toISOString(),
            'social_links' => $this->social_links,
            'settings' => $this->settings,
            'team_members' => EmployerUserResource::collection($this->whenLoaded('teamMembers')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get raw storage path for frontend URL resolution.
     * 
     * IMPORTANT: Returns the RAW path as stored in DB.
     * Frontend's getSafeImageUrl() handles building the full URL.
     * This gives frontend complete control over domain/port.
     */
    protected function getRawPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        
        // If it's already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        
        // Return raw path - frontend will build the full URL
        return $path;
    }
    
    /**
     * Get full URL for a storage path (for backward compatibility).
     * Used when absolute URL is needed server-side (e.g., PDF generation).
     */
    protected function getFullUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }
}
