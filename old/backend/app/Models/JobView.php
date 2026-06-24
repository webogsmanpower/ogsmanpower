<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobView extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'viewer_id',
        'ip_address',
        'user_agent',
        'country_code',
        'city',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the job posting that was viewed.
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    /**
     * Get the user who viewed the job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /**
     * Scope to get views for a specific job
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope to get views within a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get unique views by IP address
     */
    public function scopeUniqueByIp($query)
    {
        return $query->distinct('ip_address');
    }

    /**
     * Scope to get unique views by user
     */
    public function scopeUniqueByUser($query)
    {
        return $query->whereNotNull('viewer_id')
            ->distinct('viewer_id');
    }
}
