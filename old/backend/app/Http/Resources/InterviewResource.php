<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * InterviewResource
 * 
 * API Resource for Interview model.
 */
class InterviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $scheduledAt = $this->scheduled_at;
        $employer = $this->whenLoaded('employer');
        $jobApplication = $this->whenLoaded('jobApplication');
        $jobPosting = $jobApplication instanceof \App\Models\JobApplication ? $jobApplication->jobPosting : null;

        return [
            'id' => $this->id,
            'job_application_id' => $this->job_application_id,
            'employer_id' => $this->employer_id,
            'seeker_id' => $this->seeker_id,
            'scheduled_by' => $this->scheduled_by,
            'interview_type' => $this->interview_type,
            'title' => $this->title,
            'round_number' => $this->round_number,
            'scheduled_at' => $scheduledAt?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'timezone' => $this->timezone,
            'location' => $this->location,
            'meeting_link' => $this->meeting_link,
            'meeting_id' => $this->meeting_id,
            'instructions' => $this->instructions,
            'interviewers' => $this->interviewers,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'candidate_confirmed_at' => $this->candidate_confirmed_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'questions' => $this->questions,
            'feedback' => $this->feedback,
            'feedback_summary' => $this->feedback_summary,
            'rating' => $this->rating,
            'outcome' => $this->outcome,
            'recommendation' => $this->recommendation,
            'is_upcoming' => $this->isUpcoming(),
            'is_today' => $this->isToday(),
            
            // Frontend-friendly fields
            'companyName' => ($employer instanceof \App\Models\Employer) ? $employer->company_name : 'Unknown Company',
            'companyInitials' => $this->getCompanyInitials(($employer instanceof \App\Models\Employer) ? $employer->company_name : null),
            'jobTitle' => $jobPosting?->title ?? 'Unknown Position',
            'interviewDate' => $scheduledAt?->format('Y-m-d'),
            'interviewTime' => $scheduledAt?->format('H:i'),
            'interviewType' => $this->getInterviewTypeLabel(),
            'outcome' => $this->getOutcomeLabel(),
            
            // Relationships
            'seeker' => new SeekerResource($this->whenLoaded('seeker')),
            'job_application' => new JobApplicationResource($jobApplication),
            'employer' => $employer,
            'scheduled_by_user' => new UserResource($this->whenLoaded('scheduledBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get company initials from company name.
     */
    private function getCompanyInitials(?string $companyName): string
    {
        if (!$companyName) {
            return '??';
        }

        $words = preg_split('/\s+/', trim($companyName));
        $initials = '';

        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return substr($initials, 0, 2);
    }

    /**
     * Get interview type label.
     */
    private function getInterviewTypeLabel(): string
    {
        return match($this->interview_type) {
            'phone' => 'Phone Call',
            'video' => 'Video Call',
            'in_person' => 'In-Person',
            'technical' => 'Technical Assessment',
            'panel' => 'Panel Interview',
            default => ucfirst($this->interview_type ?? 'Interview'),
        };
    }

    /**
     * Get outcome label for past interviews.
     */
    private function getOutcomeLabel(): ?string
    {
        return match($this->outcome) {
            'pass' => 'next_round',
            'fail' => 'review',
            'pending' => 'review',
            'on_hold' => 'review',
            default => null,
        };
    }
}
