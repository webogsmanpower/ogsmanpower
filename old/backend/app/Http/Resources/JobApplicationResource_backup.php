<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JobApplicationResource
 * 
 * API Resource for JobApplication model.
 */
class JobApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_posting_id' => $this->job_posting_id,
            'seeker_id' => $this->seeker_id,
            'employer_id' => $this->employer_id,
            'resume_snapshot' => $this->resume_snapshot,
            'cover_letter' => $this->cover_letter,
            'answers' => $this->answers,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_changed_at' => $this->status_changed_at?->toISOString(),
            'status_changed_by' => $this->status_changed_by,
            'rejection_reason' => $this->rejection_reason,
            'rejection_feedback' => $this->rejection_feedback,
            'notes' => $this->notes,
            'rating' => $this->rating,
            'tags' => $this->tags,
            'source' => $this->source,
            'is_favorite' => $this->is_favorite,
            'is_viewed' => $this->is_viewed,
            'viewed_at' => $this->viewed_at?->toISOString(),
            'can_transition_to' => $this->getAvailableTransitions(),
            'job_posting' => new JobPostingResource($this->whenLoaded('jobPosting')),
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'seeker' => new SeekerResource($this->whenLoaded('seeker')),
            'interviews' => InterviewResource::collection($this->whenLoaded('interviews')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
            'document_verifications' => DocumentVerificationResource::collection($this->whenLoaded('documentVerifications')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get available status transitions.
     */
    protected function getAvailableTransitions(): array
    {
        return \App\Models\JobApplication::STATUS_FLOW[$this->status] ?? [];
    }
}
