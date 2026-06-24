<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'assessment_id',
        'is_mandatory',
        'price_paid',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'price_paid' => 'decimal:2',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}
