<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InterviewReminder Model
 * 
 * Manages interview reminders for users.
 */
class InterviewReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'interview_id',
        'remind_at',
        'is_sent',
        'sent_at',
        'reminder_type',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the interview.
     */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    /**
     * Scope for pending reminders.
     */
    public function scopePending($query)
    {
        return $query->where('is_sent', false)
            ->where('remind_at', '<=', now());
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Get reminder type label.
     */
    public function getReminderTypeLabelAttribute(): string
    {
        return match($this->reminder_type) {
            '1_hour' => '1 hour before',
            '1_day' => '1 day before',
            '2_hours' => '2 hours before',
            '30_minutes' => '30 minutes before',
            default => ucfirst($this->reminder_type),
        };
    }
}
