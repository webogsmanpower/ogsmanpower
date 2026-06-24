<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * JobApplication Model
 * 
 * Tracks seeker applications with pipeline status.
 */
class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'seeker_id',
        'employer_id',
        'resume_snapshot',
        'cover_letter',
        'current_salary',
        'expected_salary',
        'answers',
        'status',
        'status_changed_at',
        'status_changed_by',
        'rejection_reason',
        'rejection_feedback',
        'notes',
        'rating',
        'tags',
        'source',
        'agent_id',
        'agency_id',
        'referral_code',
        'is_favorite',
        'is_viewed',
        'viewed_at',
    ];

    protected $casts = [
        'resume_snapshot' => 'array',
        'answers' => 'array',
        'tags' => 'array',
        'status_changed_at' => 'datetime',
        'viewed_at' => 'datetime',
        'is_favorite' => 'boolean',
        'is_viewed' => 'boolean',
        'rating' => 'integer',
    ];

    /**
     * Application status flow.
     */
    public const STATUS_FLOW = [
        'applied' => ['reviewed', 'shortlisted', 'rejected', 'withdrawn'],
        'reviewed' => ['shortlisted', 'rejected', 'withdrawn'],
        'shortlisted' => ['interview_scheduled', 'rejected', 'withdrawn'],
        'interview_scheduled' => ['interviewed', 'rejected', 'withdrawn'],
        'interviewed' => ['contract_sent', 'shortlisted', 'rejected', 'withdrawn'],
        'contract_sent' => ['hired', 'rejected', 'withdrawn'],
        'hired' => [],
        'rejected' => [],
        'withdrawn' => [],
    ];

    /**
     * Get the job posting.
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * Get the seeker/applicant.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the user who changed the status.
     */
    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    /**
     * Get all interviews.
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * Get the latest interview.
     */
    public function latestInterview(): HasOne
    {
        return $this->hasOne(Interview::class)->latestOfMany();
    }

    /**
     * Get the contract.
     */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * Get document verifications.
     */
    public function documentVerifications(): HasMany
    {
        return $this->hasMany(DocumentVerification::class);
    }

    /**
     * Get conversations.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get screening question answers.
     */
    public function screeningAnswers(): HasMany
    {
        return $this->hasMany(ScreeningQuestionAnswer::class);
    }

    /**
     * Get assessment attempts.
     */
    public function assessmentAttempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    /**
     * Update status with tracking.
     */
    public function updateStatus(string $newStatus, ?User $changedBy = null, ?string $reason = null): bool
    {
        $allowedTransitions = self::STATUS_FLOW[$this->status] ?? [];
        
        if (!in_array($newStatus, $allowedTransitions)) {
            return false;
        }

        $this->status = $newStatus;
        $this->status_changed_at = now();
        $this->status_changed_by = $changedBy?->id;

        if ($newStatus === 'rejected' && $reason) {
            $this->rejection_reason = $reason;
        }

        return $this->save();
    }

    /**
     * Mark as viewed.
     */
    public function markAsViewed(): void
    {
        if (!$this->is_viewed) {
            $this->update([
                'is_viewed' => true,
                'viewed_at' => now(),
            ]);
        }
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(): void
    {
        $this->update(['is_favorite' => !$this->is_favorite]);
    }

    /**
     * Scope for status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for favorites.
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope for unviewed.
     */
    public function scopeUnviewed($query)
    {
        return $query->where('is_viewed', false);
    }

    /**
     * Check if can transition to status.
     */
    public function canTransitionTo(string $status): bool
    {
        $allowedTransitions = self::STATUS_FLOW[$this->status] ?? [];
        return in_array($status, $allowedTransitions);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'applied' => 'Applied',
            'reviewed' => 'Reviewed',
            'shortlisted' => 'Shortlisted',
            'interview_scheduled' => 'Interview Scheduled',
            'interviewed' => 'Interviewed',
            'contract_sent' => 'Contract Sent',
            'hired' => 'Hired',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
            default => ucfirst($this->status),
        };
    }
}
