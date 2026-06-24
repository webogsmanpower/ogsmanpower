<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * ContractMessage Model
 * 
 * Encrypted Q&A messaging for contract-specific discussions.
 */
class ContractMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'sender_id',
        'content',
        'attachments',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the contract.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the sender.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Encrypt content before saving.
     */
    public function setContentAttribute($value): void
    {
        $this->attributes['content'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt content when retrieving.
     */
    public function getContentAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Return encrypted value if decryption fails (for debugging)
            return '[Decryption failed]';
        }
    }

    /**
     * Encrypt attachments before saving.
     */
    public function setAttachmentsAttribute($value): void
    {
        if ($value) {
            $this->attributes['attachments'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['attachments'] = null;
        }
    }

    /**
     * Decrypt attachments when retrieving.
     */
    public function getAttachmentsAttribute($value): ?array
    {
        if (!$value) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if message is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if user can view this message.
     * Only sender or contract parties can view.
     */
    public function canBeViewedBy(User $user): bool
    {
        $contract = $this->contract;
        
        // Sender can always view
        if ($this->sender_id === $user->id) {
            return true;
        }

        // Contract parties can view
        $seeker = $contract->seeker;
        $employer = $contract->employer;

        // Check if user is the seeker
        if ($seeker && $seeker->user_id === $user->id) {
            return true;
        }

        // Check if user belongs to employer
        if ($employer) {
            $employerUserIds = $employer->users()->pluck('users.id')->toArray();
            if (in_array($user->id, $employerUserIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope for contract.
     */
    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    /**
     * Scope for unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
