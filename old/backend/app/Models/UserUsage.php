<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUsage extends Model
{
    protected $fillable = [
        'user_id',
        'feature_key',
        'count',
        'reset_at',
    ];

    protected $casts = [
        'reset_at' => 'date',
        'count' => 'integer',
    ];

    /**
     * Get the user that owns this usage record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this usage record has expired and should be reset.
     */
    public function shouldReset(): bool
    {
        return now()->greaterThan($this->reset_at);
    }

    /**
     * Reset the usage counter for a new billing period.
     */
    public function resetCounter(): void
    {
        $this->update([
            'count' => 0,
            'reset_at' => now()->addMonth()->startOfMonth(),
        ]);
    }
}
