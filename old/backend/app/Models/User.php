<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Billable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'password',
        'mobile',
        'date_of_birth',
        'height',
        'weight',
        'chest_measurement',
        'is_onboarding_completed',
        'role',
        'active_role',
        'super_admin',
        'banned_at',
        'ban_reason',
        'banned_by',
        'last_login_at',
        'current_login_at',
        'last_login_ip',
        'employer_id',
        'credit_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
            'current_login_at' => 'datetime',
            'banned_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
            'chest_measurement' => 'decimal:2',
            'is_onboarding_completed' => 'boolean',
            'super_admin' => 'boolean',
            'credit_balance' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly([
                'name',
                'email',
                'role',
                'active_role',
                'super_admin',
                'banned_at',
                'credit_balance',
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
            ])
            ->dontSubmitEmptyLogs();
    }

    public function seeker(): HasOne
    {
        return $this->hasOne(Seeker::class);
    }

    public function seekerResume(): HasOne
    {
        return $this->hasOne(SeekerResume::class);
    }

    /**
     * Get the employer profile (if user is an employer owner).
     */
    public function employer(): HasOne
    {
        return $this->hasOne(Employer::class);
    }

    /**
     * Get employer memberships (teams user belongs to).
     */
    public function employerMemberships(): HasMany
    {
        return $this->hasMany(EmployerUser::class);
    }

    /**
     * Get job alerts for the user.
     */
    public function jobAlerts(): HasMany
    {
        return $this->hasMany(JobAlert::class);
    }

    /**
     * Get all employers user has access to (owned + memberships).
     */
    public function accessibleEmployers()
    {
        $ownedEmployerIds = $this->employer ? [$this->employer->id] : [];
        $memberEmployerIds = $this->employerMemberships()
            ->where('is_active', true)
            ->pluck('employer_id')
            ->toArray();

        return Employer::whereIn('id', array_merge($ownedEmployerIds, $memberEmployerIds));
    }

    /**
     * Check if user is an employer.
     * Strict RBAC: Only checks primary role, no dual-role support.
     */
    public function isEmployer(): bool
    {
        return $this->role === 'employer';
    }

    /**
     * Check if user is a seeker.
     * Strict RBAC: Only checks primary role, no dual-role support.
     */
    public function isSeeker(): bool
    {
        return $this->role === 'seeker';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get current active role.
     * Note: With strict RBAC, active_role is deprecated. Use role instead.
     */
    public function getCurrentRole(): string
    {
        return $this->role ?? 'seeker';
    }

    /**
     * Get the user's current active subscription.
     */
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Get all subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->super_admin === true || $this->hasRole('super_admin');
    }
}
