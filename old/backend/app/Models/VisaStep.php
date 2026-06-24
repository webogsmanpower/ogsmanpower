<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VisaStep Model
 * 
 * Individual step tracking for visa processing workflow.
 */
class VisaStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_status_id',
        'step_name',
        'step_order',
        'status',
        'started_at',
        'completed_at',
        'notes',
        'completed_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'step_order' => 'integer',
    ];

    /**
     * Standard visa workflow steps with order.
     */
    public const WORKFLOW_STEPS = [
        1 => ['name' => 'documents_pending', 'label' => 'Document Collection', 'description' => 'Collect required documents from candidate'],
        2 => ['name' => 'documents_submitted', 'label' => 'Documents Submitted', 'description' => 'Documents submitted for verification'],
        3 => ['name' => 'documents_verified', 'label' => 'Documents Verified', 'description' => 'All documents verified and approved'],
        4 => ['name' => 'medical_scheduled', 'label' => 'Medical Scheduled', 'description' => 'Medical examination scheduled'],
        5 => ['name' => 'medical_completed', 'label' => 'Medical Completed', 'description' => 'Medical examination completed'],
        6 => ['name' => 'medical_cleared', 'label' => 'Medical Cleared', 'description' => 'Medical clearance obtained'],
        7 => ['name' => 'visa_applied', 'label' => 'Visa Applied', 'description' => 'Visa application submitted'],
        8 => ['name' => 'visa_processing', 'label' => 'Visa Processing', 'description' => 'Visa application under processing'],
        9 => ['name' => 'visa_approved', 'label' => 'Visa Approved', 'description' => 'Visa approved and stamped'],
        10 => ['name' => 'travel_scheduled', 'label' => 'Travel Scheduled', 'description' => 'Flight and travel arrangements made'],
        11 => ['name' => 'departed', 'label' => 'Departed', 'description' => 'Candidate has departed'],
        12 => ['name' => 'arrived', 'label' => 'Arrived', 'description' => 'Candidate has arrived at destination'],
        13 => ['name' => 'completed', 'label' => 'Completed', 'description' => 'Visa process completed successfully'],
    ];

    /**
     * Get the visa status.
     */
    public function visaStatus(): BelongsTo
    {
        return $this->belongsTo(VisaStatus::class);
    }

    /**
     * Get the user who completed.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get documents uploaded for this step.
     */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(VisaStepDocument::class)->latest();
    }

    /**
     * Get questionnaires for this step.
     */
    public function questionnaires(): HasMany
    {
        return $this->hasMany(VisaStepQuestionnaire::class);
    }

    /**
     * Get employer uploads for this step.
     */
    public function employerUploads(): HasMany
    {
        return $this->hasMany(VisaStepEmployerUpload::class);
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
    public function complete(?User $user = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user?->id,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Mark step as skipped.
     */
    public function skip(?string $reason = null): void
    {
        $this->update([
            'status' => 'skipped',
            'notes' => $reason,
        ]);
    }

    /**
     * Mark step as blocked.
     */
    public function block(?string $reason = null): void
    {
        $this->update([
            'status' => 'blocked',
            'notes' => $reason,
        ]);
    }

    /**
     * Get step label.
     */
    public function getLabelAttribute(): string
    {
        return self::WORKFLOW_STEPS[$this->step_order]['label'] ?? ucfirst(str_replace('_', ' ', $this->step_name));
    }

    /**
     * Get step description.
     */
    public function getDescriptionAttribute(): string
    {
        return self::WORKFLOW_STEPS[$this->step_order]['description'] ?? '';
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
     * Create all steps for a visa status.
     */
    public static function createAllForVisaStatus(int $visaStatusId): void
    {
        foreach (self::WORKFLOW_STEPS as $order => $step) {
            self::create([
                'visa_status_id' => $visaStatusId,
                'step_name' => $step['name'],
                'step_order' => $order,
                'status' => 'pending',
            ]);
        }
    }
}
