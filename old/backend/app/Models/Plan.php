<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'slug',
        'description',
        'description_ar',
        'price',
        'interval',
        'is_addon',
        'role_type',
        'features',
        'limits',
        'bilingual_cv_price',
        'is_active',
        'sort_order',
        'stripe_price_id',
        'paypal_plan_id',
        'trial_days',
        'discount_enabled',
        'discount_percentage',
        'discount_valid_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'bilingual_cv_price' => 'decimal:2',
        'is_addon' => 'boolean',
        'features' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
        'discount_enabled' => 'boolean',
        'discount_percentage' => 'decimal:2',
        'discount_valid_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Boot method to auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });

        static::updating(function ($plan) {
            if ($plan->isDirty('name') && empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    /**
     * Get subscriptions for this plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get plans by role type.
     */
    public function scopeForRole($query, string $role)
    {
        return $query->where('role_type', $role);
    }

    /**
     * Scope to get only add-on plans.
     */
    public function scopeAddons($query)
    {
        return $query->where('is_addon', true);
    }

    /**
     * Scope to get only subscription plans (not add-ons).
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('is_addon', false);
    }

    /**
     * Scope to get plans by role type.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role_type', $role);
    }

    /**
     * Scope to order plans by sort_order then name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get formatted price with currency symbol.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get display text for interval.
     */
    public function getIntervalDisplayAttribute(): string
    {
        return match($this->interval) {
            'monthly' => 'per month',
            'yearly' => 'per year',
            'one_time' => 'one time',
            default => $this->interval,
        };
    }

    /**
     * Get plan type display text.
     */
    public function getTypeDisplayAttribute(): string
    {
        if ($this->is_addon) {
            return 'Add-on';
        }
        
        return match($this->role_type) {
            'employer' => 'Employer Plan',
            'seeker' => 'Seeker Plan',
            default => 'General Plan',
        };
    }

    /**
     * Get role-specific limits structure.
     */
    public function getRoleLimitsStructure(): array
    {
        $defaultLimits = $this->limits ?? [];
        
        return match($this->role_type) {
            'seeker' => [
                'cv_downloads' => $defaultLimits['cv_downloads'] ?? 0,
                'applications' => $defaultLimits['applications'] ?? 0,
                'featured_profile' => $defaultLimits['featured_profile'] ?? false,
                'bilingual_cv' => $defaultLimits['bilingual_cv'] ?? false,
                'priority_application' => $defaultLimits['priority_application'] ?? false,
                'resume_review' => $defaultLimits['resume_review'] ?? 0,
            ],
            'employer' => [
                'job_posts' => $defaultLimits['job_posts'] ?? 0,
                'featured_jobs' => $defaultLimits['featured_jobs'] ?? 0,
                'cv_access' => $defaultLimits['cv_access'] ?? 0,
                'urgent_label' => $defaultLimits['urgent_label'] ?? 0,
                'database_access' => $defaultLimits['database_access'] ?? false,
                'company_highlighting' => $defaultLimits['company_highlighting'] ?? false,
            ],
            default => $defaultLimits,
        };
    }

    /**
     * Check if a specific feature is enabled.
     */
    public function hasFeature(string $featureKey): bool
    {
        $features = $this->features ?? [];
        return $features[$featureKey] ?? false;
    }

    /**
     * Get limit for a specific feature.
     */
    public function getLimit(string $limitKey): int
    {
        $limits = $this->limits ?? [];
        return $limits[$limitKey] ?? 0;
    }

    /**
     * Get default features structure for seeker plans.
     */
    public static function getDefaultSeekerFeatures(): array
    {
        return [
            'find_jobs' => true,
            'job_matches' => false,
            'apply_for_job' => true,
            'job_alerts' => false,
            'generate_cv' => true,
        ];
    }

    /**
     * Get default limits structure for seeker plans.
     */
    public static function getDefaultSeekerLimits(): array
    {
        return [
            'application_limit' => 10,
            'cv_download_limit' => 5,
            'bilingual_cv_limit' => 0,
            'job_alerts_limit' => 1,
        ];
    }
}
