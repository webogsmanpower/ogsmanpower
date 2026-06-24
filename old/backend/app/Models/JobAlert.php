<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_title',
        'industry',
        'country',
        'city',
        'skills',
        'frequency',
        'is_active',
    ];

    protected $casts = [
        'skills' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the job alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to only include active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by frequency.
     */
    public function scopeFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Get the display name for the alert.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->job_title) {
            return $this->job_title;
        }
        
        if ($this->industry) {
            return $this->industry . ' Jobs';
        }
        
        if ($this->skills && count($this->skills) > 0) {
            return implode(', ', array_slice($this->skills, 0, 2)) . ' Jobs';
        }
        
        return 'General Job Alert';
    }

    /**
     * Get the location display.
     */
    public function getLocationDisplayAttribute(): string
    {
        $parts = [];
        
        if ($this->city) {
            $parts[] = $this->city;
        }
        
        if ($this->country) {
            $parts[] = $this->country;
        }
        
        return implode(', ', $parts) ?: 'Anywhere';
    }
}
