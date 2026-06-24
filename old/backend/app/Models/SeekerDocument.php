<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SeekerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'verification_status',
        'verified_by',
        'rejection_reason',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Document types that can be uploaded
     */
    const DOCUMENT_TYPES = [
        'passport' => 'Passport',
        'passport_photo' => 'Passport Photo',
        'cnic_front' => 'CNIC Front',
        'cnic_back' => 'CNIC Back',
        'drivers_license' => 'Driver License',
        'medical_certificate' => 'Medical Certificate',
        'police_certificate' => 'Police Certificate',
        'introductory_video' => 'Introductory Video',
        'degree_certificate' => 'Degree Certificate',
        'transcript' => 'Academic Transcript',
        'experience_letter' => 'Experience Letter',
        'other' => 'Other Document',
    ];

    /**
     * Verification statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the user that owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified the document.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope to get documents by verification status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('verification_status', $status);
    }

    /**
     * Scope to get pending documents
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', self::STATUS_PENDING);
    }

    /**
     * Scope to get verified documents
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::STATUS_VERIFIED);
    }

    /**
     * Scope to get rejected documents
     */
    public function scopeRejected($query)
    {
        return $query->where('verification_status', self::STATUS_REJECTED);
    }

    /**
     * Check if document is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::STATUS_VERIFIED;
    }

    /**
     * Check if document is pending verification
     */
    public function isPending(): bool
    {
        return $this->verification_status === self::STATUS_PENDING;
    }

    /**
     * Check if document is rejected
     */
    public function isRejected(): bool
    {
        return $this->verification_status === self::STATUS_REJECTED;
    }

    /**
     * Get the display name for the document type
     */
    public function getDocumentTypeDisplayNameAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? ucfirst($this->document_type);
    }

    /**
     * Get the public URL for the document
     */
    public function getPublicUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Verify the document
     */
    public function verify(int $verifiedBy): void
    {
        $this->update([
            'verification_status' => self::STATUS_VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the document
     */
    public function reject(string $reason, int $verifiedBy): void
    {
        $this->update([
            'verification_status' => self::STATUS_REJECTED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Reset to pending status
     */
    public function resetToPending(): void
    {
        $this->update([
            'verification_status' => self::STATUS_PENDING,
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
        ]);
    }
}
