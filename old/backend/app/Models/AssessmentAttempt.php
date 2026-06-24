<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'seeker_id',
        'job_application_id',
        'score',
        'total_points',
        'percentage',
        'status',
        'answers',
        'started_at',
        'completed_at',
        'time_spent_seconds',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_points' => 'integer',
        'percentage' => 'decimal:2',
        'answers' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent_seconds' => 'integer',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'passed', 'failed']);
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function calculateResult(): void
    {
        $passingScore = $this->assessment->passing_score;
        $this->percentage = $this->total_points > 0 
            ? round(($this->score / $this->total_points) * 100, 2) 
            : 0;
        
        $this->status = $this->percentage >= $passingScore ? 'passed' : 'failed';
        $this->completed_at = now();
        
        if ($this->started_at) {
            $this->time_spent_seconds = now()->diffInSeconds($this->started_at);
        }
    }
}
