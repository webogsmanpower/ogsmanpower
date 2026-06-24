<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * VisaStepResource
 * 
 * API Resource for VisaStep model.
 */
class VisaStepResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $documents = [];

        // Try to load documents from relationship first
        if ($this->relationLoaded('documents')) {
            if ($this->documents && (is_array($this->documents) ? count($this->documents) > 0 : $this->documents->count() > 0)) {
                $documents = collect($this->documents)->map(function ($document) {
                    // Handle the actual document structure from the database
                    $docId = is_array($document) ? ($document['id'] ?? uniqid()) : $document->id;
                    $docFilename = is_array($document) ? $document['filename'] : $document->filename;
                    $docStatus = is_array($document) ? ($document['status'] ?? 'uploaded') : $document->status;
                    $docRejectionReason = is_array($document) ? ($document['rejection_reason'] ?? null) : $document->rejection_reason;
                    $docSeekerId = is_array($document) ? ($document['uploaded_by'] ?? null) : $document->seeker_id;
                    $docCreatedAt = is_array($document) ? ($document['uploaded_at'] ?? null) : $document->created_at;
                    $docPath = is_array($document) ? ($document['path'] ?? null) : null;
                    $docSize = is_array($document) ? ($document['size'] ?? null) : null;
                    
                    return [
                        'id' => $docId,
                        'filename' => $docFilename,
                        'status' => $docStatus,
                        'rejection_reason' => $docRejectionReason,
                        'url' => $docPath ? url("/storage/{$docPath}") : null,
                        'uploaded_by' => 'seeker', // All visa step documents are uploaded by seekers
                        'seeker_id' => $docSeekerId,
                        'uploaded_at' => $docCreatedAt,
                        'file_size' => $docSize ? $this->formatBytes($docSize) : 'Unknown',
                    ];
                });
            }
        } elseif (is_array($this->documents)) {
            $documents = collect($this->documents)->map(function ($document) {
                return [
                    ...$document,
                    'uploaded_by' => 'seeker', // Default to seeker for visa step documents
                    'uploaded_at' => $document['created_at'] ?? $document['uploaded_at'],
                    'file_size' => $document['file_size'] ?? 'Unknown',
                ];
            });
        } else {
            // If relationship not loaded, load documents manually
            $manualDocuments = \App\Models\VisaStepDocument::where('visa_step_id', $this->id)
                ->latest()
                ->get();
                
            $documents = $manualDocuments->map(function ($document) {
                return [
                    'id' => $document->id,
                    'filename' => $document->filename,
                    'status' => $document->status,
                    'rejection_reason' => $document->rejection_reason,
                    'url' => $document->getUrl(),
                    'uploaded_by' => 'seeker',
                    'seeker_id' => $document->seeker_id,
                    'uploaded_at' => $document->created_at?->toISOString(),
                    'file_size' => $document->getFormattedSize() ?? 'Unknown',
                ];
            });
        }

        // Load questionnaires
        $questionnaires = [];
        if ($this->relationLoaded('questionnaires')) {
            $questionnaires = $this->questionnaires->map(function ($questionnaire) {
                return [
                    'id' => $questionnaire->id,
                    'title' => $questionnaire->title,
                    'description' => $questionnaire->description,
                    'question_type' => $questionnaire->question_type,
                    'options' => $questionnaire->options,
                    'is_required' => $questionnaire->is_required,
                    'sort_order' => $questionnaire->sort_order,
                    'created_at' => $questionnaire->created_at?->toISOString(),
                ];
            });
        }

        // Load employer uploads (only visible ones)
        $employerUploads = [];
        if ($this->relationLoaded('employerUploads')) {
            $employerUploads = $this->employerUploads
                ->where('is_visible_to_seeker', true)
                ->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'title' => $upload->title,
                        'description' => $upload->description,
                        'file_name' => $upload->file_name,
                        'file_type' => $upload->file_type,
                        'file_size' => $upload->file_size,
                        'url' => Storage::url($upload->file_path),
                        'created_at' => $upload->created_at?->toISOString(),
                    ];
                });
        }

        return [
            'id' => $this->id,
            'visa_status_id' => $this->visa_status_id,
            'step_name' => $this->step_name,
            'step_order' => $this->step_order,
            'status' => $this->status,
            'label' => $this->label,
            'description' => $this->description,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'notes' => $this->notes,
            'documents' => $documents,
            'questionnaires' => $questionnaires,
            'employer_uploads' => $employerUploads,
            'completed_by' => $this->completed_by,
            'is_completed' => $this->isCompleted(),
            'is_in_progress' => $this->isInProgress(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
