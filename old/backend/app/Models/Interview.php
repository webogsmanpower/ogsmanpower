<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Interview Model
 * 
 * Interview scheduling and tracking for the hiring pipeline.
 */
class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_application_id',
        'employer_id',
        'seeker_id',
        'scheduled_by',
        'interview_type',
        'title',
        'round_number',
        'scheduled_at',
        'duration_minutes',
        'timezone',
        'location',
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'instructions',
        'interviewers',
        'status',
        'candidate_confirmed_at',
        'cancellation_reason',
        'cancelled_by',
        'notes',
        'questions',
        'feedback',
        'feedback_summary',
        'rating',
        'outcome',
        'recommendation',
        'reminder_sent',
        'reminder_sent_at',
    ];

    protected $casts = [
        'interviewers' => 'array',
        'questions' => 'array',
        'feedback' => 'array',
        'scheduled_at' => 'datetime',
        'candidate_confirmed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'duration_minutes' => 'integer',
        'round_number' => 'integer',
        'rating' => 'integer',
    ];

    /**
     * Get the job application.
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the seeker/candidate.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Get the user who scheduled.
     */
    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /**
     * Get the user who cancelled.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Scope for upcoming interviews.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->whereIn('status', ['scheduled', 'confirmed']);
    }

    /**
     * Scope for today's interviews.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today())
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress']);
    }

    /**
     * Scope for pending feedback.
     */
    public function scopePendingFeedback($query)
    {
        return $query->where('status', 'completed')
            ->whereNull('outcome');
    }

    /**
     * Confirm by candidate.
     */
    public function confirmByCandidate(): void
    {
        $this->update([
            'status' => 'confirmed',
            'candidate_confirmed_at' => now(),
        ]);
    }

    /**
     * Cancel interview.
     */
    public function cancel(?User $cancelledBy = null, ?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_by' => $cancelledBy?->id,
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Add feedback.
     */
    public function addFeedback(array $feedback, ?int $rating = null, ?string $outcome = null): void
    {
        $this->update([
            'feedback' => $feedback,
            'rating' => $rating,
            'outcome' => $outcome,
        ]);
    }

    /**
     * Check if interview is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->scheduled_at->isFuture() 
            && in_array($this->status, ['scheduled', 'confirmed']);
    }

    /**
     * Check if interview is today.
     */
    public function isToday(): bool
    {
        return $this->scheduled_at->isToday();
    }

    /**
     * Get end time.
     */
    public function getEndTimeAttribute(): \Carbon\Carbon
    {
        return $this->scheduled_at->addMinutes($this->duration_minutes);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rescheduled' => 'Rescheduled',
            'no_show' => 'No Show',
            default => ucfirst($this->status),
        };
    }
}
