<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserUsage;
use App\Models\Subscription;
use Carbon\Carbon;

class UsageService
{
    /**
     * Feature key constants
     */
    const FEATURE_APPLICATION = 'application';
    const FEATURE_BILINGUAL_CV = 'bilingual_cv';
    const FEATURE_STANDARD_CV = 'standard_cv';
    const FEATURE_JOB_ALERT = 'job_alert';

    /**
     * Check if user can perform a specific action based on their plan limits.
     *
     * @param User $user
     * @param string $featureKey
     * @return bool
     */
    public function canUse(User $user, string $featureKey): bool
    {
        $plan = $this->getUserPlan($user);
        
        if (!$plan) {
            return false; // No plan = no access
        }

        // Check if feature is enabled in plan
        if (!$this->isFeatureEnabled($plan, $featureKey)) {
            return false;
        }

        // Get the limit for this feature
        $limit = $this->getFeatureLimit($plan, $featureKey);
        
        // If limit is -1, it means unlimited
        if ($limit === -1) {
            return true;
        }

        // Get current usage
        $currentUsage = $this->getCurrentUsage($user, $featureKey);

        return $currentUsage < $limit;
    }

    /**
     * Get remaining usage for a feature.
     *
     * @param User $user
     * @param string $featureKey
     * @return array ['remaining' => int, 'limit' => int, 'used' => int]
     */
    public function getRemaining(User $user, string $featureKey): array
    {
        $plan = $this->getUserPlan($user);
        
        if (!$plan) {
            return ['remaining' => 0, 'limit' => 0, 'used' => 0];
        }

        $limit = $this->getFeatureLimit($plan, $featureKey);
        $used = $this->getCurrentUsage($user, $featureKey);
        
        // Unlimited
        if ($limit === -1) {
            return ['remaining' => -1, 'limit' => -1, 'used' => $used];
        }

        $remaining = max(0, $limit - $used);

        return [
            'remaining' => $remaining,
            'limit' => $limit,
            'used' => $used,
        ];
    }

    /**
     * Increment usage counter for a feature.
     *
     * @param User $user
     * @param string $featureKey
     * @param int $amount
     * @return void
     */
    public function increment(User $user, string $featureKey, int $amount = 1): void
    {
        $resetAt = now()->addMonth()->startOfMonth();
        
        $usage = UserUsage::firstOrCreate(
            [
                'user_id' => $user->id,
                'feature_key' => $featureKey,
                'reset_at' => $resetAt,
            ],
            [
                'count' => 0,
            ]
        );

        // Check if usage record has expired
        if ($usage->shouldReset()) {
            $usage->resetCounter();
        }

        $usage->increment('count', $amount);
    }

    /**
     * Get current usage count for a feature.
     *
     * @param User $user
     * @param string $featureKey
     * @return int
     */
    public function getCurrentUsage(User $user, string $featureKey): int
    {
        $currentPeriod = now()->startOfMonth();
        
        $usage = UserUsage::where('user_id', $user->id)
            ->where('feature_key', $featureKey)
            ->where('reset_at', '>=', $currentPeriod)
            ->first();

        if (!$usage) {
            return 0;
        }

        // Auto-reset if expired
        if ($usage->shouldReset()) {
            $usage->resetCounter();
            return 0;
        }

        return $usage->count;
    }

    /**
     * Get user's active plan.
     *
     * @param User $user
     * @return \App\Models\Plan|null
     */
    protected function getUserPlan(User $user)
    {
        // Get active subscription
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('role', 'seeker')
            ->with('plan')
            ->first();

        return $subscription?->plan;
    }

    /**
     * Check if a feature is enabled in the plan.
     *
     * @param \App\Models\Plan $plan
     * @param string $featureKey
     * @return bool
     */
    protected function isFeatureEnabled($plan, string $featureKey): bool
    {
        $features = $plan->features ?? [];
        
        // Map feature keys to plan feature flags
        $featureMap = [
            self::FEATURE_APPLICATION => 'apply_for_job',
            self::FEATURE_BILINGUAL_CV => 'generate_cv',
            self::FEATURE_STANDARD_CV => 'generate_cv',
            self::FEATURE_JOB_ALERT => 'job_alerts',
        ];

        $planFeatureKey = $featureMap[$featureKey] ?? $featureKey;

        return $features[$planFeatureKey] ?? false;
    }

    /**
     * Get the limit for a specific feature from the plan.
     *
     * @param \App\Models\Plan $plan
     * @param string $featureKey
     * @return int (-1 for unlimited, 0 for pay-per-use)
     */
    protected function getFeatureLimit($plan, string $featureKey): int
    {
        $limits = $plan->limits ?? [];
        
        // Map feature keys to limit keys
        $limitMap = [
            self::FEATURE_APPLICATION => 'application_limit',
            self::FEATURE_BILINGUAL_CV => 'bilingual_cv_limit',
            self::FEATURE_STANDARD_CV => 'cv_download_limit',
            self::FEATURE_JOB_ALERT => 'job_alerts_limit',
        ];

        $limitKey = $limitMap[$featureKey] ?? $featureKey . '_limit';

        return $limits[$limitKey] ?? 0;
    }

    /**
     * Get bilingual CV pricing info.
     *
     * @param User $user
     * @return array
     */
    public function getBilingualCVPricing(User $user): array
    {
        $plan = $this->getUserPlan($user);
        
        if (!$plan) {
            return [
                'has_credits' => false,
                'remaining' => 0,
                'price_per_download' => 1.00,
                'requires_payment' => true,
            ];
        }

        $remaining = $this->getRemaining($user, self::FEATURE_BILINGUAL_CV);
        
        return [
            'has_credits' => $remaining['remaining'] > 0 || $remaining['remaining'] === -1,
            'remaining' => $remaining['remaining'],
            'limit' => $remaining['limit'],
            'used' => $remaining['used'],
            'price_per_download' => $plan->bilingual_cv_price ?? 1.00,
            'requires_payment' => $remaining['remaining'] === 0,
        ];
    }

    /**
     * Reset all usage counters for a user (admin override).
     *
     * @param User $user
     * @return void
     */
    public function resetAllUsage(User $user): void
    {
        UserUsage::where('user_id', $user->id)->delete();
    }

    /**
     * Get usage summary for all features.
     *
     * @param User $user
     * @return array
     */
    public function getUsageSummary(User $user): array
    {
        $plan = $this->getUserPlan($user);
        
        if (!$plan) {
            return [];
        }

        return [
            'applications' => $this->getRemaining($user, self::FEATURE_APPLICATION),
            'standard_cv' => $this->getRemaining($user, self::FEATURE_STANDARD_CV),
            'bilingual_cv' => $this->getRemaining($user, self::FEATURE_BILINGUAL_CV),
            'job_alerts' => $this->getRemaining($user, self::FEATURE_JOB_ALERT),
        ];
    }
}
