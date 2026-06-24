<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Message Model
 * 
 * Individual messages within a conversation.
 */
class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'message_type',
        'attachments',
        'reply_to_id',
        'read_at',
        'metadata',
        'is_edited',
        'edited_at',
        'is_deleted',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    /**
     * Get the conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the parent message (if reply).
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /**
     * Get replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    /**
     * Get read receipts.
     */
    public function readReceipts(): HasMany
    {
        return $this->hasMany(MessageReadReceipt::class);
    }

    /**
     * Mark as read by user.
     */
    public function markReadBy(User $user): void
    {
        if ($this->sender_id === $user->id) {
            return; // Don't mark own messages as read
        }

        MessageReadReceipt::firstOrCreate([
            'message_id' => $this->id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);
    }

    /**
     * Check if read by user.
     */
    public function isReadBy(int $userId): bool
    {
        return $this->readReceipts()->where('user_id', $userId)->exists();
    }

    /**
     * Edit message.
     */
    public function edit(string $newMessage): void
    {
        $this->update([
            'message' => $newMessage,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Soft delete message (mark as deleted but keep record).
     */
    public function softDeleteMessage(): void
    {
        $this->update([
            'is_deleted' => true,
            'message' => '[Message deleted]',
        ]);
    }

    /**
     * Scope for unread messages.
     */
    public function scopeUnreadFor($query, int $userId)
    {
        return $query->where('sender_id', '!=', $userId)
            ->whereDoesntHave('readReceipts', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }
}
