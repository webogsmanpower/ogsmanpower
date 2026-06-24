<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * VisaWorkflowStepResource
 * 
 * API Resource for VisaWorkflowStep model.
 */
class VisaWorkflowStepResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visa_status_id' => $this->visa_status_id,
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'is_mandatory' => $this->is_mandatory,
            'requires_documents' => $this->requires_documents,
            'requires_questionnaires' => $this->requires_questionnaires,
            'requires_employer_uploads' => $this->requires_employer_uploads,
            'required_documents' => $this->required_documents,
            'questionnaire_config' => $this->questionnaire_config,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'completed_by' => $this->completed_by,
            'public_notes' => $this->public_notes,
            'internal_notes' => $this->internal_notes,
            'is_completed' => $this->isCompleted(),
            'is_in_progress' => $this->isInProgress(),
            'questionnaires' => $this->whenLoaded('questionnaires'),
            'employer_uploads' => $this->whenLoaded('employerUploads'),
            'documents' => $this->whenLoaded('documents'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
