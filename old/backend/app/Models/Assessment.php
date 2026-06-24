<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'type',
        'creator_id',
        'employer_id',
        'price',
        'time_limit_minutes',
        'passing_score',
        'is_active',
        'shuffle_questions',
        'show_results',
        'category',
        'settings',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'time_limit_minutes' => 'integer',
        'passing_score' => 'integer',
        'is_active' => 'boolean',
        'shuffle_questions' => 'boolean',
        'show_results' => 'boolean',
        'settings' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('sort_order');
    }

    public function jobPostings(): BelongsToMany
    {
        return $this->belongsToMany(JobPosting::class, 'job_assessments')
            ->withPivot(['is_mandatory', 'price_paid'])
            ->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdminStandard($query)
    {
        return $query->where('type', 'admin_standard');
    }

    public function scopeEmployerCustom($query)
    {
        return $query->where('type', 'employer_custom');
    }

    public function scopeForEmployer($query, $employerId)
    {
        return $query->where('employer_id', $employerId);
    }

    public function getTotalPointsAttribute(): int
    {
        return $this->questions->sum('points');
    }

    public function getQuestionCountAttribute(): int
    {
        return $this->questions->count();
    }

    public function isAdminTest(): bool
    {
        return $this->type === 'admin_standard';
    }

    public function isPaid(): bool
    {
        return $this->price > 0;
    }
}
