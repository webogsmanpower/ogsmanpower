<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * EmployerService
 * 
 * Business logic for employer operations.
 */
class EmployerService
{
    /**
     * Create a new employer profile.
     */
    public function createEmployer(User $user, array $data): Employer
    {
        // Handle logo upload
        $logoPath = null;
        if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
            $logoPath = $data['logo']->store('employer/logos', 'public');
        }

        // Update user role
        $user->update(['role' => 'employer']);

        return Employer::create([
            'user_id' => $user->id,
            ...$data,
            'logo_path' => $logoPath,
        ]);
    }

    /**
     * Get employer for a user (either owned or as team member).
     */
    public function getEmployerForUser(User $user): ?Employer
    {
        // First check if user owns an employer
        if ($user->employer) {
            return $user->employer;
        }

        // Check if user is a team member
        $membership = $user->employerMemberships()
            ->where('is_active', true)
            ->whereNotNull('accepted_at')
            ->first();

        return $membership?->employer;
    }

    /**
     * Update employer profile.
     */
    public function updateEmployer(Employer $employer, array $data): Employer
    {
        $employer->update($data);
        return $employer->fresh();
    }

    /**
     * Upload employer logo.
     */
    public function uploadLogo(Employer $employer, UploadedFile $file): string
    {
        // Delete old logo if exists
        if ($employer->logo_path) {
            Storage::disk('public')->delete($employer->logo_path);
        }

        $path = $file->store('employers/logos', 'public');
        $employer->update(['logo_path' => $path]);

        return $path;
    }

    /**
     * Get dashboard statistics.
     * 
     * OPTIMIZED: Uses withCount and conditional aggregates to minimize queries.
     * Before: ~12 separate queries
     * After: ~5 queries with eager loading
     */
    public function getDashboardStats(Employer $employer): array
    {
        // Single query for job stats using conditional counts
        $jobStats = $employer->jobPostings()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as active
            ')
            ->first();

        // Single query for application stats using conditional counts
        $appStats = $employer->applications()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "applied" THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN status = "shortlisted" THEN 1 ELSE 0 END) as shortlisted,
                SUM(CASE WHEN status = "hired" THEN 1 ELSE 0 END) as hired
            ')
            ->first();

        // Single query for interview stats using conditional counts
        $interviewStats = $employer->interviews()
            ->selectRaw('
                SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN scheduled_at > NOW() AND status IN ("scheduled", "confirmed") THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN DATE(scheduled_at) = CURDATE() THEN 1 ELSE 0 END) as today
            ')
            ->first();

        // Contract and visa counts (still separate but minimal)
        $pendingContracts = $employer->contracts()->whereIn('status', ['sent', 'viewed'])->count();
        $activeVisas = $employer->visaStatuses()
            ->whereNotIn('current_step', ['completed', 'visa_rejected'])
            ->count();

        // Recent activity with eager loading (prevents N+1)
        $recentApplications = $employer->applications()
            ->with(['seeker.user', 'jobPosting:id,title,location_city'])
            ->latest()
            ->take(5)
            ->get();

        // Today's interviews with eager loading
        $todayInterviews = $employer->interviews()
            ->with(['seeker.user:id,name,email', 'jobApplication.jobPosting:id,title'])
            ->whereDate('scheduled_at', today())
            ->get();

        return [
            'jobs' => [
                'active' => (int) ($jobStats->active ?? 0),
                'total' => (int) ($jobStats->total ?? 0),
            ],
            'applications' => [
                'total' => (int) ($appStats->total ?? 0),
                'new' => (int) ($appStats->new_count ?? 0),
                'shortlisted' => (int) ($appStats->shortlisted ?? 0),
            ],
            'interviews' => [
                'scheduled' => (int) ($interviewStats->scheduled ?? 0),
                'upcoming' => (int) ($interviewStats->upcoming ?? 0),
                'today' => (int) ($interviewStats->today ?? 0),
            ],
            'hiring' => [
                'hired' => (int) ($appStats->hired ?? 0),
                'pending_contracts' => $pendingContracts,
                'active_visas' => $activeVisas,
            ],
            'recent_applications' => $recentApplications,
            'today_interviews' => $todayInterviews,
        ];
    }
}
