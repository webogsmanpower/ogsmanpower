<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Conversation Model
 * 
 * Groups messages between participants.
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'job_application_id',
        'subject',
        'participants',
        'last_message_at',
        'is_archived',
        'is_closed',
    ];

    protected $casts = [
        'participants' => 'array',
        'last_message_at' => 'datetime',
        'is_archived' => 'boolean',
        'is_closed' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($conversation) {
            if (empty($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the job application.
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * Get all messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get latest message.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Add participant.
     */
    public function addParticipant(int $userId, string $role): void
    {
        $participants = $this->participants ?? [];
        $participants[] = [
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => now()->toISOString(),
        ];
        $this->update(['participants' => $participants]);
    }

    /**
     * Check if user is participant.
     */
    public function hasParticipant(int $userId): bool
    {
        $participants = $this->participants ?? [];
        return collect($participants)->contains('user_id', $userId);
    }

    /**
     * Get unread count for user.
     */
    public function getUnreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereDoesntHave('readReceipts', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();
    }

    /**
     * Update last message timestamp.
     */
    public function touchLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Archive conversation.
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Close conversation.
     */
    public function close(): void
    {
        $this->update(['is_closed' => true]);
    }

    /**
     * Scope for user's conversations.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereJsonContains('participants', ['user_id' => $userId]);
    }

    /**
     * Scope for active (not archived).
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
