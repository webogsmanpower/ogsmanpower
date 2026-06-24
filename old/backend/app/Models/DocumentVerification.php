<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DocumentVerification Model
 * 
 * Tracks employer verification of seeker documents.
 */
class DocumentVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'seeker_id',
        'job_application_id',
        'document_type',
        'document_name',
        'document_number',
        'document_path',
        'document_issue_date',
        'document_expiry_date',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'rejection_details',
        'notes',
        'verification_checklist',
        'is_original_verified',
        'requires_resubmission',
    ];

    protected $casts = [
        'verification_checklist' => 'array',
        'document_issue_date' => 'date',
        'document_expiry_date' => 'date',
        'verified_at' => 'datetime',
        'is_original_verified' => 'boolean',
        'requires_resubmission' => 'boolean',
    ];

    /**
     * Document types.
     */
    public const DOCUMENT_TYPES = [
        'passport',
        'cnic',
        'national_id',
        'drivers_license',
        'degree_certificate',
        'experience_certificate',
        'medical_certificate',
        'police_clearance',
        'birth_certificate',
        'marriage_certificate',
        'professional_license',
        'training_certificate',
        'other',
    ];

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
     * Get the job application.
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * Get the user who verified.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Verify document.
     */
    public function verify(User $verifier, ?string $notes = null): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'notes' => $notes,
            'requires_resubmission' => false,
        ]);
    }

    /**
     * Reject document.
     */
    public function reject(User $verifier, string $reason, ?string $details = null): void
    {
        $this->update([
            'status' => 'rejected',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
            'rejection_details' => $details,
            'requires_resubmission' => true,
        ]);
    }

    /**
     * Mark as in review.
     */
    public function markInReview(): void
    {
        $this->update(['status' => 'in_review']);
    }

    /**
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        return $this->document_expiry_date && $this->document_expiry_date->isPast();
    }

    /**
     * Scope for status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending verification.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_review']);
    }

    /**
     * Scope for verified.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope for document type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'in_review' => 'In Review',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->document_type));
    }
}
