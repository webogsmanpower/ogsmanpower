<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateActionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'seeker_id',
        'job_application_id',
        'created_by',
        'request_type',
        'title',
        'message',
        'is_required',
        'due_date',
        'status',
        'response_text',
        'response_file_path',
        'responded_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'due_date' => 'date',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the employer that created this request.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the seeker this request is for.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Get the job application this request is related to.
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * Get the user who created this request.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the request is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }
        
        if ($this->due_date && $this->due_date->isPast() && $this->status === 'pending') {
            return true;
        }
        
        return false;
    }

    /**
     * Get the response file URL.
     */
    public function getResponseFileUrlAttribute(): ?string
    {
        if (!$this->response_file_path) {
            return null;
        }
        
        return url('storage/' . $this->response_file_path);
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get requests for a specific seeker.
     */
    public function scopeForSeeker($query, $seekerId)
    {
        return $query->where('seeker_id', $seekerId);
    }

    /**
     * Scope to get requests from a specific employer.
     */
    public function scopeFromEmployer($query, $employerId)
    {
        return $query->where('employer_id', $employerId);
    }
}
