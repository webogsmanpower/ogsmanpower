<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Event;

/**
 * VisaStatus Model
 * 
 * Tracks visa processing steps for hired candidates.
 */
class VisaStatus extends Model
{
    use HasFactory, LogsActivity;

    protected static function boot()
    {
        parent::boot();

        // Create visa steps when a new visa status is created
        static::created(function ($visaStatus) {
            VisaStep::createAllForVisaStatus($visaStatus->id);
            
            // Create default dynamic workflow steps
            VisaWorkflowStep::createDefaultWorkflow($visaStatus->id);
            
            // Populate documents_required from workflow steps
            $workflowSteps = \App\Models\VisaWorkflowStep::where('visa_status_id', $visaStatus->id)
                ->whereNotNull('required_documents')
                ->get();
            
            $allRequiredDocuments = [];
            foreach ($workflowSteps as $step) {
                if (is_array($step->required_documents)) {
                    $allRequiredDocuments = array_merge($allRequiredDocuments, $step->required_documents);
                }
            }
            
            if (!empty($allRequiredDocuments)) {
                $visaStatus->documents_required = array_unique($allRequiredDocuments);
                $visaStatus->save();
            }
            
            // Mark the current step as completed if it's not 'not_started'
            if ($visaStatus->current_step !== 'not_started') {
                $currentStep = VisaStep::where('visa_status_id', $visaStatus->id)
                    ->where('step_name', $visaStatus->current_step)
                    ->first();
                    
                if ($currentStep) {
                    $currentStep->update(['status' => 'completed']);
                }
            }
        });
    }

    protected $fillable = [
        'contract_id',
        'employer_id',
        'seeker_id',
        'visa_type',
        'destination_country',
        'origin_country',
        'current_step',
        'step_history',
        'documents_required',
        'documents_submitted',
        'documents_verified',
        'medical_date',
        'medical_center',
        'medical_result',
        'medical_notes',
        'medical_certificate_path',
        'visa_application_date',
        'visa_application_number',
        'visa_number',
        'visa_issue_date',
        'visa_expiry_date',
        'visa_document_path',
        'visa_rejection_reason',
        'travel_date',
        'flight_number',
        'departure_airport',
        'arrival_airport',
        'departure_time',
        'arrival_time',
        'actual_arrival_date',
        'accommodation_address',
        'accommodation_contact',
        'notes',
        'last_updated_by',
    ];

    protected static $logAttributes = [
        'current_step',
        'visa_type',
        'destination_country',
        'medical_result',
        'visa_application_date',
        'visa_number',
        'visa_issue_date',
        'visa_expiry_date',
        'travel_date',
        'flight_number',
        'actual_arrival_date',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['current_step', 'visa_type', 'destination_country', 'medical_result', 'visa_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if ($eventName === 'updated' && $this->wasChanged('current_step')) {
            $oldStep = $this->getOriginal('current_step');
            $newStep = $this->current_step;
            return "Visa step updated from {$oldStep} to {$newStep}";
        }
        
        return "Visa status {$eventName}";
    }

    protected $casts = [
        'step_history' => 'array',
        'documents_required' => 'array',
        'documents_submitted' => 'array',
        'documents_verified' => 'array',
        'medical_date' => 'date',
        'visa_application_date' => 'date',
        'visa_issue_date' => 'date',
        'visa_expiry_date' => 'date',
        'travel_date' => 'date',
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'actual_arrival_date' => 'date',
    ];

    /**
     * Visa processing steps in order.
     */
    public const STEPS = [
        'not_started',
        'documents_pending',
        'documents_submitted',
        'documents_verified',
        'medical_scheduled',
        'medical_completed',
        'medical_cleared',
        'visa_applied',
        'visa_processing',
        'visa_approved',
        'visa_rejected',
        'travel_scheduled',
        'departed',
        'arrived',
        'completed',
    ];

    /**
     * Get the contract.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the seeker.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Get the user who last updated.
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Get the visa steps.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(VisaStep::class)->orderBy('step_order');
    }

    /**
     * Get the process steps (including custom).
     */
    public function processSteps(): HasMany
    {
        return $this->hasMany(VisaProcessStep::class);
    }

    /**
     * Get the dynamic workflow steps.
     */
    public function workflowSteps(): HasMany
    {
        return $this->hasMany(VisaWorkflowStep::class)->orderBy('sort_order');
    }

    /**
     * Request documents at any step.
     */
    public function requestDocuments(array $documents, ?User $requestedBy = null, ?string $note = null, ?string $targetStep = null): bool
    {
        try {
            $targetStep = $targetStep ?? $this->current_step;
            
            // Find or create the visa step for the target step
            $visaStep = $this->steps()->where('step_name', $targetStep)->first();
            if (!$visaStep) {
                // Create the visa step if it doesn't exist
                $stepOrder = array_search($targetStep, self::STEPS) + 1;
                $visaStep = VisaStep::create([
                    'visa_status_id' => $this->id,
                    'step_name' => $targetStep,
                    'step_order' => $stepOrder,
                    'status' => $targetStep === $this->current_step ? 'in_progress' : 'pending',
                    'started_at' => $targetStep === $this->current_step ? now() : null,
                ]);
            }
            
            // Update documents_required array to include new documents
            $currentRequired = $this->documents_required ?? [];
            $newRequired = array_unique(array_merge(
                $currentRequired,
                array_column($documents, 'name')
            ));
            
            $this->documents_required = $newRequired;
            $this->save();

            // If documents are requested at a different step, update the current step
            if ($targetStep !== $this->current_step && in_array($targetStep, self::STEPS)) {
                $this->updateStep($targetStep, $requestedBy, $note);
            }

            // Create activity log for document request
            activity()
                ->performedOn($this)
                ->causedBy($requestedBy)
                ->withProperties([
                    'seeker_id' => $this->seeker_id,
                    'employer_id' => $this->employer_id,
                    'documents_requested' => $documents,
                    'note' => $note,
                    'target_step' => $targetStep,
                ])
                ->log("Documents requested for step '{$targetStep}': " . implode(', ', array_column($documents, 'label')));

            return true;
        } catch (\Exception $e) {
            \Log::error('Document request failed:', [
                'visa_id' => $this->id,
                'error' => $e->getMessage(),
                'documents' => $documents,
            ]);
            
            return false;
        }
    }

    /**
     * Get all pending document requests (both standard and custom).
     */
    public function getPendingDocumentRequests(): array
    {
        $pendingRequests = [];
        
        // Get custom process steps that are pending
        $customSteps = $this->processSteps()->custom()->withStatus('pending')->get();
        foreach ($customSteps as $step) {
            $pendingRequests[] = [
                'id' => $step->id,
                'name' => $step->name,
                'label' => $step->label,
                'type' => 'custom',
                'step_id' => $step->id,
                'is_process_step' => true,
                'target_step' => $step->target_step,
            ];
        }
        
        // Get standard documents from documents_required that haven't been uploaded
        $requiredDocs = $this->documents_required ?? [];
        foreach ($requiredDocs as $docName) {
            // Check if document is already uploaded in any step
            $isUploaded = $this->steps()
                ->whereHas('documents', function ($query) use ($docName) {
                    $query->where('filename', 'like', '%' . $docName . '%');
                })
                ->exists();
                
            if (!$isUploaded) {
                $pendingRequests[] = [
                    'id' => null,
                    'name' => $docName,
                    'label' => ucfirst(str_replace('_', ' ', $docName)),
                    'type' => 'standard',
                    'step_id' => null,
                    'is_process_step' => false,
                ];
            }
        }
        
        return $pendingRequests;
    }

    /**
     * Check if there are any pending document requests.
     */
    public function hasPendingDocumentRequests(): bool
    {
        return count($this->getPendingDocumentRequests()) > 0;
    }

    /**
     * Update step with history tracking.
     */
    public function updateStep(string $newStep, ?User $updatedBy = null, ?string $note = null): bool
    {
        \Log::info('VisaStatus::updateStep called:', [
            'visa_id' => $this->id,
            'current_step' => $this->current_step,
            'new_step' => $newStep,
            'available_steps' => self::STEPS
        ]);

        // Check if the step is actually changing
        if ($this->current_step === $newStep) {
            \Log::info('Step is not changing, skipping update:', [
                'visa_id' => $this->id,
                'current_step' => $this->current_step,
                'new_step' => $newStep
            ]);
            return true; // Return true since no change is needed
        }

        if (!in_array($newStep, self::STEPS)) {
            \Log::error('Invalid step provided:', [
                'new_step' => $newStep,
                'available_steps' => self::STEPS
            ]);
            return false;
        }

        $oldStep = $this->current_step;

        $history = $this->step_history ?? [];
        $history[] = [
            'from_step' => $oldStep,
            'to_step' => $newStep,
            'changed_at' => now()->toISOString(),
            'changed_by' => $updatedBy?->id,
            'note' => $note,
        ];

        $this->current_step = $newStep;
        $this->step_history = $history;
        $this->last_updated_by = $updatedBy?->id;

        \Log::info('About to save visa status:', [
            'visa_id' => $this->id,
            'old_step' => $oldStep,
            'new_step' => $newStep
        ]);

        $result = $this->save();

        \Log::info('Visa status save result:', [
            'visa_id' => $this->id,
            'result' => $result,
            'current_step_after_save' => $this->fresh()->current_step
        ]);

        // Update the corresponding visa step
        if ($result) {
            // Mark the new step as completed
            $newVisaStep = VisaStep::where('visa_status_id', $this->id)
                ->where('step_name', $newStep)
                ->first();
            
            if ($newVisaStep) {
                \Log::info('Updating visa step completion:', ['step_id' => $newVisaStep->id]);
                $newVisaStep->complete($updatedBy, $note);
            } else {
                \Log::warning('Visa step not found for completion:', [
                    'visa_status_id' => $this->id,
                    'step_name' => $newStep
                ]);
            }
        }

        // Create activity log for seeker notification
        if ($result && $this->seeker) {
            activity()
                ->performedOn($this)
                ->causedBy($updatedBy)
                ->withProperties([
                    'seeker_id' => $this->seeker_id,
                    'employer_id' => $this->employer_id,
                    'old_step' => $oldStep,
                    'new_step' => $newStep,
                    'note' => $note,
                    'visa_type' => $this->visa_type,
                    'destination_country' => $this->destination_country,
                ])
                ->log("Visa step updated from {$oldStep} to {$newStep}" . ($note ? ": {$note}" : ""));

            // Send notification to seeker
            if ($this->seeker->user) {
                $this->seeker->user->notify(
                    new \App\Notifications\VisaStepUpdateNotification(
                        $oldStep,
                        $newStep,
                        $this->visa_type ?? 'Work Visa',
                        $note,
                        $this->employer?->company_name
                    )
                );
            }
        }

        return $result;
    }

    /**
     * Get step index (for progress calculation).
     */
    public function getStepIndex(): int
    {
        return array_search($this->current_step, self::STEPS) ?: 0;
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): int
    {
        $totalSteps = count(self::STEPS) - 1; // Exclude 'not_started'
        $currentIndex = $this->getStepIndex();
        
        if ($currentIndex === 0) {
            return 0;
        }

        return (int) round(($currentIndex / $totalSteps) * 100);
    }

    /**
     * Check if visa is approved.
     */
    public function isApproved(): bool
    {
        return in_array($this->current_step, ['visa_approved', 'travel_scheduled', 'departed', 'arrived', 'completed']);
    }

    /**
     * Check if visa is rejected.
     */
    public function isRejected(): bool
    {
        return $this->current_step === 'visa_rejected';
    }

    /**
     * Check if process is complete.
     */
    public function isComplete(): bool
    {
        return $this->current_step === 'completed';
    }

    /**
     * Scope for active visas (not completed or rejected).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('current_step', ['completed', 'visa_rejected']);
    }

    /**
     * Scope for step.
     */
    public function scopeAtStep($query, string $step)
    {
        return $query->where('current_step', $step);
    }

    /**
     * Get step label.
     */
    public function getStepLabelAttribute(): string
    {
        return match($this->current_step) {
            'not_started' => 'Not Started',
            'documents_pending' => 'Documents Pending',
            'documents_submitted' => 'Documents Submitted',
            'documents_verified' => 'Documents Verified',
            'medical_scheduled' => 'Medical Scheduled',
            'medical_completed' => 'Medical Completed',
            'medical_cleared' => 'Medical Cleared',
            'visa_applied' => 'Visa Applied',
            'visa_processing' => 'Visa Processing',
            'visa_approved' => 'Visa Approved',
            'visa_rejected' => 'Visa Rejected',
            'travel_scheduled' => 'Travel Scheduled',
            'departed' => 'Departed',
            'arrived' => 'Arrived',
            'completed' => 'Completed',
            default => ucfirst(str_replace('_', ' ', $this->current_step)),
        };
    }
}
