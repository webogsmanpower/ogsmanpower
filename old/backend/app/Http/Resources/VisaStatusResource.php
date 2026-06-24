<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * VisaStatusResource
 * 
 * API Resource for VisaStatus model.
 */
class VisaStatusResource extends JsonResource
{
    /**
     * Get step label based on step name and order.
     */
    private function getStepLabel($stepName, $stepOrder): string
    {
        $workflowSteps = [
            0 => 'Not Started',
            1 => 'Document Collection',
            2 => 'Documents Submitted',
            3 => 'Documents Verified',
            4 => 'Medical Scheduled',
            5 => 'Medical Completed',
            6 => 'Medical Cleared',
            7 => 'Visa Applied',
            8 => 'Visa Processing',
            9 => 'Visa Approved',
            10 => 'Visa Rejected',
            11 => 'Travel Scheduled',
            12 => 'Departed',
            13 => 'Arrived',
            14 => 'Completed'
        ];

        return $workflowSteps[$stepOrder] ?? ucfirst(str_replace('_', ' ', $stepName));
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'employer_id' => $this->employer_id,
            'seeker_id' => $this->seeker_id,
            'visa_type' => $this->visa_type,
            'destination_country' => $this->destination_country,
            'origin_country' => $this->origin_country,
            'current_step' => $this->current_step,
            'step_label' => $this->step_label,
            'step_history' => $this->step_history,
            'progress_percentage' => $this->getProgressPercentage(),
            'documents_required' => $this->documents_required,
            'documents_submitted' => $this->documents_submitted,
            'documents_verified' => $this->documents_verified,
            'pending_document_requests' => $this->when(isset($this->documents_required), function() {
                // Simple approach: just return the required documents without complex queries
                return is_array($this->documents_required) ? $this->documents_required : [];
            }),
            'has_pending_document_requests' => $this->when(isset($this->documents_required), function() {
                return !empty($this->documents_required);
            }),
            'medical_date' => $this->medical_date?->toDateString(),
            'medical_center' => $this->medical_center,
            'medical_result' => $this->medical_result,
            'medical_notes' => $this->medical_notes,
            'visa_application_date' => $this->visa_application_date?->toDateString(),
            'visa_application_number' => $this->visa_application_number,
            'visa_number' => $this->visa_number,
            'visa_issue_date' => $this->visa_issue_date?->toDateString(),
            'visa_expiry_date' => $this->visa_expiry_date?->toDateString(),
            'visa_rejection_reason' => $this->visa_rejection_reason,
            'travel_date' => $this->travel_date?->toDateString(),
            'flight_number' => $this->flight_number,
            'departure_airport' => $this->departure_airport,
            'arrival_airport' => $this->arrival_airport,
            'departure_time' => $this->departure_time?->toISOString(),
            'arrival_time' => $this->arrival_time?->toISOString(),
            'actual_arrival_date' => $this->actual_arrival_date?->toDateString(),
            'accommodation_address' => $this->accommodation_address,
            'accommodation_contact' => $this->accommodation_contact,
            'notes' => $this->notes,
            'is_approved' => $this->current_step === 'approved',
            'is_rejected' => $this->current_step === 'rejected',
            'is_complete' => $this->current_step === 'completed',
            'steps' => $this->whenLoaded('steps', function() {
                return $this->steps->map(function($step) {
                    return [
                        'id' => $step->id,
                        'visa_status_id' => $step->visa_status_id,
                        'name' => $step->step_name,
                        'label' => $this->getStepLabel($step->step_name, $step->step_order),
                        'status' => $step->status,
                        'sort_order' => $step->step_order,
                        'started_at' => $step->started_at?->toISOString(),
                        'completed_at' => $step->completed_at?->toISOString(),
                        'documents' => $step->relationLoaded('uploadedDocuments') && $step->uploadedDocuments ? $step->uploadedDocuments->map(function($document) {
                            return [
                                'id' => $document->id,
                                'filename' => $document->filename,
                                'path' => $document->path,
                                'url' => $document->path ? '/storage/' . $document->path : null,
                                'status' => $document->status,
                                'rejection_reason' => $document->rejection_reason,
                                'verification_notes' => $document->verification_notes,
                                'uploaded_by' => $document->uploaded_by ?? 'seeker',
                                'file_size' => $document->file_size,
                                'uploaded_at' => $document->created_at?->toISOString(),
                            ];
                        }) : [],
                    ];
                });
            }),
            'process_steps' => $this->when($this->relationLoaded('processSteps'), function () {
                return $this->processSteps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'visa_status_id' => $step->visa_status_id,
                        'name' => $step->name,
                        'label' => $step->label,
                        'status' => $step->status,
                        'is_custom' => $step->is_custom,
                        'documents' => $step->relationLoaded('documents') && $step->documents ? $step->documents->map(function($document) {
                            return [
                                'id' => $document->id,
                                'filename' => $document->filename,
                                'requirement_name' => $document->requirement_name,
                                'status' => $document->status,
                                'rejection_reason' => $document->rejection_reason,
                                'url' => $document->path ? '/storage/' . $document->path : null,
                                'path' => $document->path,
                                'uploaded_at' => $document->created_at?->toISOString(),
                            ];
                        }) : [],
                        'created_at' => $step->created_at?->toISOString(),
                    ];
                });
            }),
            'seeker' => $this->whenLoaded('seeker', function() {
                return [
                    'id' => $this->seeker->id,
                    'user' => [
                        'name' => $this->seeker->user->name ?? null,
                        'email' => $this->seeker->user->email ?? null,
                    ],
                    'first_name' => $this->seeker->first_name,
                    'last_name' => $this->seeker->last_name,
                    'profile_image_path' => $this->seeker->profile_image_path,
                    'profile_image_url' => $this->seeker->profile_image_path 
                        ? url('/storage/' . $this->seeker->profile_image_path) 
                        : null,
                    'profession' => $this->seeker->profession,
                    'headline' => $this->seeker->headline,
                ];
            }),
            'employer' => $this->whenLoaded('employer'),
            'contract' => $this->whenLoaded('contract'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
