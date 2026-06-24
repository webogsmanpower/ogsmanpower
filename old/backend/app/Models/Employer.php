<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employer Model
 * 
 * Represents a company/employer profile in the system.
 * Each employer is linked to a user (owner/admin) and can have multiple team members.
 */
class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'company_name_ar',
        'trade_license_number',
        'registration_number',
        'license_number',
        'registration_document_path',
        'company_type',
        'industry',
        'company_size',
        'country',
        'city',
        'address',
        'phone',
        'email',
        'company_email',
        'company_phone',
        'website',
        'logo_path',
        'description',
        'description_ar',
        'is_verified',
        'verification_status',
        'rejection_reason',
        'rejection_date',
        'rejected_by',
        'verified_at',
        'verified_by',
        'settings',
        'social_links',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'rejection_date' => 'datetime',
        'settings' => 'array',
        'social_links' => 'array',
    ];

    /**
     * Get the owner/admin user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified this employer.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the admin who rejected this employer.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get all team members (sub-users).
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(EmployerUser::class);
    }

    /**
     * Get all job postings.
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * Get all job applications.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Get all interviews.
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * Get all contracts.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get all visa statuses.
     */
    public function visaStatuses(): HasMany
    {
        return $this->hasMany(VisaStatus::class);
    }

    /**
     * Get all document verifications.
     */
    public function documentVerifications(): HasMany
    {
        return $this->hasMany(DocumentVerification::class);
    }

    /**
     * Get active job postings.
     */
    public function activeJobs(): HasMany
    {
        return $this->jobPostings()->where('status', 'published');
    }

    /**
     * Check if user has access to this employer.
     */
    public function hasAccess(User $user): bool
    {
        // Owner always has access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check if user is a team member
        return $this->teamMembers()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get user's role in this employer.
     */
    public function getUserRole(User $user): ?string
    {
        if ($this->user_id === $user->id) {
            return 'admin';
        }

        $teamMember = $this->teamMembers()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $teamMember?->role;
    }

    /**
     * Get logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return str_starts_with($this->logo_path, 'http')
            ? $this->logo_path
            : config('app.url') . '/storage/' . $this->logo_path;
    }

    /**
     * Get employer settings.
     */
    public function settings(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployerSetting::class);
    }

    /**
     * Get custom assessments created by this employer.
     */
    public function customAssessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->where('type', 'employer_custom');
    }

    /**
     * Get or create employer settings with defaults.
     */
    public function getOrCreateSettings(): EmployerSetting
    {
        return $this->settings ??= EmployerSetting::firstOrCreate(
            ['employer_id' => $this->id],
            [
                'custom_test_limit' => AdminSetting::getDefaultCustomTestLimit(),
                'test_taker_limit' => AdminSetting::getDefaultTestTakerLimit(),
                'screening_questions_per_job' => AdminSetting::getDefaultScreeningQuestionsLimit(),
                'active_job_limit' => AdminSetting::getDefaultActiveJobLimit(),
            ]
        );
    }
}
