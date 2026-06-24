<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * JobPosting Model
 * 
 * Represents a job listing posted by an employer.
 */
class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'created_by',
        'title',
        'title_ar',
        'slug',
        'description',
        'description_ar',
        'requirements',
        'responsibilities',
        'job_type',
        'experience_level',
        'experience_years_min',
        'experience_years_max',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_period',
        'is_salary_visible',
        'location_country',
        'location_city',
        'location_address',
        'is_remote',
        'skills_required',
        'skills_preferred',
        'benefits',
        'languages_required',
        'vacancies',
        'application_deadline',
        'status',
        'published_at',
        'closed_at',
        'views_count',
        'applications_count',
        'extra',
        // Enhanced fields
        'contract_duration',
        'visa_type',
        'gender_preference',
        'age_min',
        'age_max',
        'housing_allowance',
        'transportation_allowance',
        'food_allowance',
        'overtime_allowance',
        'medical_insurance',
        'annual_ticket',
        'working_hours',
        'working_days',
        // Screening & Assessments
        'screening_questions',
        'assessment_ids',
    ];

    protected $casts = [
        'requirements' => 'array',
        'responsibilities' => 'array',
        'skills_required' => 'array',
        'skills_preferred' => 'array',
        'benefits' => 'array',
        'languages_required' => 'array',
        'extra' => 'array',
        'is_salary_visible' => 'boolean',
        'is_remote' => 'boolean',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'application_deadline' => 'date',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
        'vacancies' => 'integer',
        'views_count' => 'integer',
        'applications_count' => 'integer',
        // Enhanced fields casts
        'age_min' => 'integer',
        'age_max' => 'integer',
        'housing_allowance' => 'boolean',
        'transportation_allowance' => 'boolean',
        'food_allowance' => 'boolean',
        'overtime_allowance' => 'boolean',
        'medical_insurance' => 'boolean',
        'annual_ticket' => 'boolean',
        'screening_questions' => 'array',
        'assessment_ids' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->slug)) {
                $job->slug = static::generateUniqueSlug($job->title);
            }
        });
    }

    /**
     * Generate a unique slug.
     */
    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the user who created this posting.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all applications.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Get all job views.
     */
    public function views(): HasMany
    {
        return $this->hasMany(JobView::class);
    }

    /**
     * Get pending applications.
     */
    public function pendingApplications(): HasMany
    {
        return $this->applications()->where('status', 'applied');
    }

    /**
     * Get shortlisted applications.
     */
    public function shortlistedApplications(): HasMany
    {
        return $this->applications()->where('status', 'shortlisted');
    }

    /**
     * Scope for published jobs.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for active jobs (published and not expired).
     */
    public function scopeActive($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('application_deadline')
                    ->orWhere('application_deadline', '>=', now());
            });
    }

    /**
     * Scope for jobs by country.
     */
    public function scopeInCountry($query, string $country)
    {
        return $query->where('location_country', $country);
    }

    /**
     * Increment view count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment application count.
     */
    public function incrementApplications(): void
    {
        $this->increment('applications_count');
    }

    /**
     * Decrement application count.
     */
    public function decrementApplications(): void
    {
        $this->decrement('applications_count');
    }

    /**
     * Publish the job.
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Close the job.
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Get salary range display.
     */
    public function getSalaryRangeAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return null;
        }

        $currency = $this->salary_currency;
        $period = ucfirst($this->salary_period);

        if ($this->salary_min && $this->salary_max) {
            return "{$currency} {$this->salary_min} - {$this->salary_max} / {$period}";
        }

        if ($this->salary_min) {
            return "From {$currency} {$this->salary_min} / {$period}";
        }

        return "Up to {$currency} {$this->salary_max} / {$period}";
    }

    /**
     * Check if job is accepting applications.
     */
    public function isAcceptingApplications(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->application_deadline && $this->application_deadline->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get screening questions for this job.
     */
    public function screeningQuestions(): HasMany
    {
        return $this->hasMany(JobScreeningQuestion::class)->orderBy('sort_order');
    }

    /**
     * Get assessments attached to this job.
     */
    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'job_assessments')
            ->withPivot(['is_mandatory', 'price_paid'])
            ->withTimestamps();
    }

    /**
     * Get mandatory assessments.
     */
    public function mandatoryAssessments(): BelongsToMany
    {
        return $this->assessments()->wherePivot('is_mandatory', true);
    }
}
