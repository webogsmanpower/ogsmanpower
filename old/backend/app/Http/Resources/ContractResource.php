<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ContractResource
 * 
 * API Resource for Contract model.
 */
class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_application_id' => $this->job_application_id,
            'employer_id' => $this->employer_id,
            'seeker_id' => $this->seeker_id,
            'contract_number' => $this->contract_number,
            'title' => $this->title,
            'job_title' => $this->job_title,
            'department' => $this->department,
            'reporting_to' => $this->reporting_to,
            'work_location' => $this->work_location,
            'salary' => $this->salary,
            'salary_currency' => $this->salary_currency,
            'salary_period' => $this->salary_period,
            'formatted_salary' => $this->formatted_salary,
            'allowances' => $this->allowances,
            'benefits' => $this->benefits,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'contract_type' => $this->contract_type,
            'probation_months' => $this->probation_months,
            'notice_period_days' => $this->notice_period_days,
            'working_hours' => $this->working_hours,
            'working_days_per_week' => $this->working_days_per_week,
            'terms' => $this->terms,
            'special_conditions' => $this->special_conditions,
            'document_path' => $this->document_path,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'sent_at' => $this->sent_at?->toISOString(),
            'viewed_at' => $this->viewed_at?->toISOString(),
            'signed_at' => $this->signed_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'version' => $this->version,
            'is_expired' => $this->isExpired(),
            'employer' => $this->whenLoaded('employer', function () {
                return [
                    'id' => $this->employer->id,
                    'company_name' => $this->employer->company_name,
                    'logo_url' => $this->employer->logo_path 
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->employer->logo_path) 
                        : null,
                ];
            }),
            'template' => $this->whenLoaded('template', function () {
                return [
                    'id' => $this->template->id,
                    'name' => $this->template->name,
                    'content' => $this->template->content,
                    'header_image_url' => $this->template->header_image_path 
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->template->header_image_path) 
                        : null,
                    'footer_text' => $this->template->footer_text,
                ];
            }),
            'seeker' => $this->whenLoaded('seeker') ? new \App\Http\Resources\SeekerResource($this->seeker) : null,
            'job_application' => $this->whenLoaded('jobApplication') ? new \App\Http\Resources\JobApplicationResource($this->jobApplication) : null,
            'visa_status' => $this->whenLoaded('visaStatus') ? new \App\Http\Resources\VisaStatusResource($this->visaStatus) : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
