<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VisaProcessStep Model
 * 
 * Represents a step in the visa process workflow.
 * Can be standard (auto-created) or custom (employer-requested).
 */
class VisaProcessStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_status_id',
        'name',
        'label',
        'status',
        'is_custom',
        'target_step',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    /**
     * Get the visa status this step belongs to.
     */
    public function visaStatus(): BelongsTo
    {
        return $this->belongsTo(VisaStatus::class);
    }

    /**
     * Get documents uploaded for this step.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VisaStepDocument::class, 'visa_process_step_id');
    }

    /**
     * Scope for custom steps.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    /**
     * Scope for standard steps.
     */
    public function scopeStandard($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Scope for status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if step is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if step is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if step is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if step is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark step as in progress.
     */
    public function start(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    /**
     * Mark step as approved.
     */
    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * Mark step as rejected.
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
