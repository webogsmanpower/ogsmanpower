<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JobPostingResource
 * 
 * API Resource for JobPosting model.
 */
class JobPostingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'created_by' => $this->created_by,
            'created_by_name' => $this->createdBy?->name ?? 'You',
            'title' => $this->title,
            'title_ar' => $this->title_ar,
            'slug' => $this->slug,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'requirements' => $this->requirements,
            'responsibilities' => $this->responsibilities,
            'job_type' => $this->job_type,
            'experience_level' => $this->experience_level,
            'experience_years_min' => $this->experience_years_min,
            'experience_years_max' => $this->experience_years_max,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_currency' => $this->salary_currency,
            'salary_period' => $this->salary_period,
            'salary_range' => $this->salary_range,
            'is_salary_visible' => $this->is_salary_visible,
            'location_country' => $this->location_country,
            'location_city' => $this->location_city,
            'location_address' => $this->location_address,
            'is_remote' => $this->is_remote,
            // Enhanced fields
            'contract_duration' => $this->contract_duration,
            'visa_type' => $this->visa_type,
            'gender_preference' => $this->gender_preference,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max,
            'nationality_preference' => $this->nationality_preference,
            'functional_area' => $this->functional_area,
            'housing_allowance' => $this->housing_allowance,
            'transportation_allowance' => $this->transportation_allowance,
            'food_allowance' => $this->food_allowance,
            'overtime_allowance' => $this->overtime_allowance,
            'medical_insurance' => $this->medical_insurance,
            'annual_ticket' => $this->annual_ticket,
            'working_hours' => $this->working_hours,
            'working_days' => $this->working_days,
            'skills_required' => $this->skills_required,
            'skills_preferred' => $this->skills_preferred,
            'benefits' => $this->benefits,
            'languages_required' => $this->languages_required,
            'vacancies' => $this->vacancies,
            'application_deadline' => $this->application_deadline?->toDateString(),
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'views_count' => $this->views_count ?? 0,
            'applications_count' => $this->applications_count ?? 0,
            'reviewed_count' => $this->whenLoaded('applications', function () {
                return $this->applications->whereIn('status', ['reviewed', 'shortlisted', 'interview_scheduled', 'selected', 'offered', 'hired'])->count();
            }, 0),
            'shortlisted_count' => $this->whenLoaded('applications', function () {
                return $this->applications->whereIn('status', ['shortlisted', 'interview_scheduled', 'selected', 'offered', 'hired'])->count();
            }, 0),
            'interviews_count' => $this->whenLoaded('applications', function () {
                return $this->applications->whereIn('status', ['interview_scheduled', 'selected', 'offered', 'hired'])->count();
            }, 0),
            'has_applied' => $this->when(isset($this->has_applied), (int) $this->has_applied, 0),
            'is_accepting_applications' => $this->isAcceptingApplications(),
            'is_saved' => $user ? (bool) ($this->is_saved ?? false) : false,
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'applications' => JobApplicationResource::collection($this->whenLoaded('applications')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
