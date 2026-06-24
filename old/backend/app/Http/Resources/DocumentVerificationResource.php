<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * DocumentVerificationResource
 * 
 * API Resource for DocumentVerification model.
 */
class DocumentVerificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'seeker_id' => $this->seeker_id,
            'job_application_id' => $this->job_application_id,
            'document_type' => $this->document_type,
            'document_type_label' => $this->document_type_label,
            'document_name' => $this->document_name,
            'document_number' => $this->document_number,
            'document_path' => $this->document_path,
            'document_issue_date' => $this->document_issue_date?->toDateString(),
            'document_expiry_date' => $this->document_expiry_date?->toDateString(),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'rejection_details' => $this->rejection_details,
            'notes' => $this->notes,
            'verification_checklist' => $this->verification_checklist,
            'is_original_verified' => $this->is_original_verified,
            'requires_resubmission' => $this->requires_resubmission,
            'is_expired' => $this->isExpired(),
            'seeker' => new SeekerResource($this->whenLoaded('seeker')),
            'verified_by_user' => new UserResource($this->whenLoaded('verifiedBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
