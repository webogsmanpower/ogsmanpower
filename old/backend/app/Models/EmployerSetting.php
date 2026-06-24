<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'custom_test_limit',
        'test_taker_limit',
        'custom_tests_created',
        'test_takers_this_month',
        'test_takers_reset_at',
        'assessment_credits',
        'active_job_limit',
        'featured_job_limit',
        'screening_questions_per_job',
        'subscription_tier',
        'subscription_expires_at',
        'extra_settings',
    ];

    protected $casts = [
        'custom_test_limit' => 'integer',
        'test_taker_limit' => 'integer',
        'custom_tests_created' => 'integer',
        'test_takers_this_month' => 'integer',
        'test_takers_reset_at' => 'datetime',
        'assessment_credits' => 'decimal:2',
        'active_job_limit' => 'integer',
        'featured_job_limit' => 'integer',
        'screening_questions_per_job' => 'integer',
        'subscription_expires_at' => 'datetime',
        'extra_settings' => 'array',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function canCreateCustomTest(): bool
    {
        return $this->custom_tests_created < $this->custom_test_limit;
    }

    public function canAddTestTaker(): bool
    {
        $this->resetMonthlyCounterIfNeeded();
        return $this->test_takers_this_month < $this->test_taker_limit;
    }

    public function incrementCustomTests(): void
    {
        $this->increment('custom_tests_created');
    }

    public function decrementCustomTests(): void
    {
        if ($this->custom_tests_created > 0) {
            $this->decrement('custom_tests_created');
        }
    }

    public function incrementTestTakers(): void
    {
        $this->resetMonthlyCounterIfNeeded();
        $this->increment('test_takers_this_month');
    }

    public function addCredits(float $amount): void
    {
        $this->increment('assessment_credits', $amount);
    }

    public function deductCredits(float $amount): bool
    {
        if ($this->assessment_credits >= $amount) {
            $this->decrement('assessment_credits', $amount);
            return true;
        }
        return false;
    }

    protected function resetMonthlyCounterIfNeeded(): void
    {
        if (!$this->test_takers_reset_at || $this->test_takers_reset_at->isPast()) {
            $this->update([
                'test_takers_this_month' => 0,
                'test_takers_reset_at' => now()->addMonth()->startOfMonth(),
            ]);
        }
    }

    public function getRemainingCustomTestsAttribute(): int
    {
        return max(0, $this->custom_test_limit - $this->custom_tests_created);
    }

    public function getRemainingTestTakersAttribute(): int
    {
        $this->resetMonthlyCounterIfNeeded();
        return max(0, $this->test_taker_limit - $this->test_takers_this_month);
    }
}
