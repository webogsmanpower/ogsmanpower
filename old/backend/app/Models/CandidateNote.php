<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'seeker_id',
        'created_by',
        'content',
    ];

    /**
     * Get the employer that owns this note.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the seeker this note is about.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Get the user who created this note.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the created by name attribute.
     */
    public function getCreatedByNameAttribute(): string
    {
        return $this->createdBy?->name ?? 'Unknown';
    }

    /**
     * Scope to get notes for a specific seeker.
     */
    public function scopeForSeeker($query, $seekerId)
    {
        return $query->where('seeker_id', $seekerId);
    }

    /**
     * Scope to get notes from a specific employer.
     */
    public function scopeFromEmployer($query, $employerId)
    {
        return $query->where('employer_id', $employerId);
    }
}
