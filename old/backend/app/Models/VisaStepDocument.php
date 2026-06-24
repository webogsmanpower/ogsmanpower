<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisaStepDocument Model
 * 
 * Represents a document uploaded for a specific visa step.
 * Strictly linked to a step and seeker to prevent duplication.
 */
class VisaStepDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_step_id',
        'visa_process_step_id',
        'seeker_id',
        'path',
        'filename',
        'requirement_name',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'rejection_reason' => 'string',
    ];

    /**
     * Get the visa step this document belongs to.
     */
    public function visaStep(): BelongsTo
    {
        return $this->belongsTo(VisaStep::class);
    }

    /**
     * Get the visa process step this document belongs to (if custom).
     */
    public function visaProcessStep(): BelongsTo
    {
        return $this->belongsTo(VisaProcessStep::class);
    }

    /**
     * Get the seeker who uploaded this document.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Scope for uploaded documents.
     */
    public function scopeUploaded($query)
    {
        return $query->where('status', 'uploaded');
    }

    /**
     * Scope for verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope for rejected documents.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for a specific seeker.
     */
    public function scopeForSeeker($query, int $seekerId)
    {
        return $query->where('seeker_id', $seekerId);
    }

    /**
     * Check if document is uploaded.
     */
    public function isUploaded(): bool
    {
        return $this->status === 'uploaded';
    }

    /**
     * Check if document is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Check if document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Verify the document.
     */
    public function verify(): void
    {
        $this->update([
            'status' => 'verified',
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the document with a reason.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get the public URL for the document.
     */
    public function getUrl(): string
    {
        return url("/storage/{$this->path}");
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
