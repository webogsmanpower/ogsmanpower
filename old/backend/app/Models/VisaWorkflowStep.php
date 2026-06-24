<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VisaWorkflowStep Model
 * 
 * Dynamic workflow steps for visa processing.
 * Replaces hardcoded STEPS constant with customizable steps.
 */
class VisaWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_status_id',
        'name',
        'label',
        'description',
        'sort_order',
        'status',
        'is_mandatory',
        'requires_documents',
        'requires_questionnaires',
        'requires_employer_uploads',
        'required_documents',
        'questionnaire_config',
        'employer_uploads',
        'started_at',
        'completed_at',
        'completed_by',
        'public_notes',
        'internal_notes',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'questionnaire_config' => 'array',
        'employer_uploads' => 'array',
        'is_mandatory' => 'boolean',
        'requires_documents' => 'boolean',
        'requires_questionnaires' => 'boolean',
        'requires_employer_uploads' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the visa status.
     */
    public function visaStatus(): BelongsTo
    {
        return $this->belongsTo(VisaStatus::class);
    }

    /**
     * Get the user who completed the step.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get questionnaires for this step.
     */
    public function questionnaires(): HasMany
    {
        return $this->hasMany(VisaStepQuestionnaire::class, 'visa_workflow_step_id');
    }

    /**
     * Get employer uploads for this step.
     */
    public function employerUploads(): HasMany
    {
        return $this->hasMany(VisaStepEmployerUpload::class, 'visa_workflow_step_id');
    }

    /**
     * Get documents uploaded for this step.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VisaStepDocument::class)->where('visa_workflow_step_id', $this->id);
    }

    /**
     * Mark step as started.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark step as completed.
     */
    public function complete(?User $user = null, ?string $publicNotes = null, ?string $internalNotes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user?->id,
            'public_notes' => $publicNotes ?? $this->public_notes,
            'internal_notes' => $internalNotes ?? $this->internal_notes,
        ]);
    }

    /**
     * Mark step as skipped.
     */
    public function skip(?string $reason = null): void
    {
        $this->update([
            'status' => 'skipped',
            'internal_notes' => $reason,
        ]);
    }

    /**
     * Mark step as blocked.
     */
    public function block(?string $reason = null): void
    {
        $this->update([
            'status' => 'blocked',
            'internal_notes' => $reason,
        ]);
    }

    /**
     * Check if step is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if step is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Scope for status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Create default workflow steps for a visa status.
     */
    public static function createDefaultWorkflow(int $visaStatusId): void
    {
        $defaultSteps = [
            [
                'name' => 'documents_pending',
                'label' => 'Document Collection',
                'description' => 'Collect required documents from candidate',
                'sort_order' => 1,
                'is_mandatory' => true,
                'requires_documents' => true,
                'required_documents' => ['passport', 'national_id', 'medical_certificate', 'police_clearance'],
            ],
            [
                'name' => 'documents_submitted',
                'label' => 'Documents Submitted',
                'description' => 'Documents submitted for verification',
                'sort_order' => 2,
                'is_mandatory' => true,
            ],
            [
                'name' => 'documents_verified',
                'label' => 'Documents Verified',
                'description' => 'All documents verified and approved',
                'sort_order' => 3,
                'is_mandatory' => true,
            ],
            [
                'name' => 'medical_scheduled',
                'label' => 'Medical Scheduled',
                'description' => 'Medical examination scheduled',
                'sort_order' => 4,
                'is_mandatory' => true,
            ],
            [
                'name' => 'medical_completed',
                'label' => 'Medical Completed',
                'description' => 'Medical examination completed',
                'sort_order' => 5,
                'is_mandatory' => true,
            ],
            [
                'name' => 'medical_cleared',
                'label' => 'Medical Cleared',
                'description' => 'Medical clearance obtained',
                'sort_order' => 6,
                'is_mandatory' => true,
            ],
            [
                'name' => 'visa_applied',
                'label' => 'Visa Applied',
                'description' => 'Visa application submitted',
                'sort_order' => 7,
                'is_mandatory' => true,
            ],
            [
                'name' => 'visa_processing',
                'label' => 'Visa Processing',
                'description' => 'Visa application under processing',
                'sort_order' => 8,
                'is_mandatory' => true,
            ],
            [
                'name' => 'visa_approved',
                'label' => 'Visa Approved',
                'description' => 'Visa approved and stamped',
                'sort_order' => 9,
                'is_mandatory' => true,
            ],
            [
                'name' => 'travel_scheduled',
                'label' => 'Travel Scheduled',
                'description' => 'Flight and travel arrangements made',
                'sort_order' => 10,
                'is_mandatory' => true,
            ],
            [
                'name' => 'departed',
                'label' => 'Departed',
                'description' => 'Candidate has departed',
                'sort_order' => 11,
                'is_mandatory' => true,
            ],
            [
                'name' => 'arrived',
                'label' => 'Arrived',
                'description' => 'Candidate has arrived at destination',
                'sort_order' => 12,
                'is_mandatory' => true,
            ],
            [
                'name' => 'completed',
                'label' => 'Completed',
                'description' => 'Visa process completed successfully',
                'sort_order' => 13,
                'is_mandatory' => true,
            ],
        ];

        foreach ($defaultSteps as $step) {
            self::create(array_merge($step, ['visa_status_id' => $visaStatusId]));
        }
    }
}
