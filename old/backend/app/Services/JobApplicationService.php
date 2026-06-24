<?php

namespace App\Services;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Seeker;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * JobApplicationService
 * 
 * THE SINGLE SOURCE OF TRUTH for job application operations.
 * Centralizes all business logic for applying to jobs and managing applications.
 * 
 * Responsibilities:
 * - Job application creation with validation
 * - Duplicate application prevention
 * - Status management with notifications
 * - Application statistics
 * 
 * @package App\Services
 */
class JobApplicationService
{
    /**
     * Valid application statuses
     */
    public const STATUSES = [
        'applied',
        'reviewed',
        'shortlisted',
        'interview_scheduled',
        'interviewed',
        'contract_sent',
        'hired',
        'rejected',
        'withdrawn',
    ];

    /**
     * Terminal statuses (application is closed)
     */
    public const TERMINAL_STATUSES = ['hired', 'rejected', 'withdrawn'];

    /**
     * Active statuses (application is in progress)
     */
    public const ACTIVE_STATUSES = [
        'applied',
        'reviewed',
        'shortlisted',
        'interview_scheduled',
        'interviewed',
        'contract_sent',
    ];

    /**
     * Apply to a job posting.
     * 
     * Handles the complete application flow:
     * 1. Validates job is open for applications
     * 2. Checks for duplicate applications
     * 3. Creates application record
     * 4. Sends notifications
     *
     * @param Seeker $seeker
     * @param JobPosting $job
     * @param array $applicationData Optional cover letter, etc.
     * @return JobApplication
     * @throws ValidationException
     */
    public function apply(Seeker $seeker, JobPosting $job, array $applicationData = []): JobApplication
    {
        return DB::transaction(function () use ($seeker, $job, $applicationData) {
            // Validate job is accepting applications
            $this->validateJobAcceptsApplications($job);

            // Check for duplicate application
            $this->checkDuplicateApplication($seeker, $job);

            // Create the application
            $application = JobApplication::create([
                'seeker_id' => $seeker->id,
                'job_posting_id' => $job->id,
                'employer_id' => $job->employer_id,
                'status' => 'applied',
                'cover_letter' => $applicationData['cover_letter'] ?? null,
                'source' => $applicationData['source'] ?? 'direct',
                'applied_at' => now(),
            ]);

            // Increment job application count
            if (method_exists($job, 'incrementApplications')) {
                $job->incrementApplications();
            } else {
                $job->increment('applications_count');
            }

            Log::info('Job application created', [
                'application_id' => $application->id,
                'seeker_id' => $seeker->id,
                'job_id' => $job->id,
            ]);

            return $application->load(['jobPosting', 'employer']);
        });
    }

    /**
     * Get standardized application status object.
     * 
     * Returns a consistent structure for frontend consumption.
     *
     * @param Seeker $seeker
     * @param JobPosting $job
     * @return array|null
     */
    public function getApplicationStatus(Seeker $seeker, JobPosting $job): ?array
    {
        $application = JobApplication::where('seeker_id', $seeker->id)
            ->where('job_posting_id', $job->id)
            ->first();

        if (!$application) {
            return null;
        }

        return $this->formatApplicationStatus($application);
    }

    /**
     * Format application status for API response.
     *
     * @param JobApplication $application
     * @return array
     */
    public function formatApplicationStatus(JobApplication $application): array
    {
        return [
            'id' => (int) $application->id,
            'status' => (string) $application->status,
            'status_label' => $this->getStatusLabel($application->status),
            'status_color' => $this->getStatusColor($application->status),
            'is_active' => in_array($application->status, self::ACTIVE_STATUSES),
            'is_terminal' => in_array($application->status, self::TERMINAL_STATUSES),
            'applied_at' => $application->applied_at?->toIso8601String(),
            'status_changed_at' => $application->status_changed_at?->toIso8601String(),
            'can_withdraw' => $this->canWithdraw($application),
            'next_steps' => $this->getNextSteps($application->status),
        ];
    }

    /**
     * Update application status with proper notifications.
     *
     * @param JobApplication $application
     * @param string $newStatus
     * @param User|null $changedBy
     * @param string|null $reason
     * @param string|null $notes
     * @return bool
     */
    public function updateStatus(
        JobApplication $application,
        string $newStatus,
        ?User $changedBy = null,
        ?string $reason = null,
        ?string $notes = null
    ): bool {
        if (!in_array($newStatus, self::STATUSES)) {
            Log::warning('Invalid status attempted', [
                'application_id' => $application->id,
                'attempted_status' => $newStatus,
            ]);
            return false;
        }

        $previousStatus = $application->status;

        // Don't update if same status
        if ($previousStatus === $newStatus) {
            return true;
        }

        $application->status = $newStatus;
        $application->status_changed_at = now();
        $application->status_changed_by = $changedBy?->id;

        if ($reason) {
            $application->rejection_reason = $reason;
        }

        if ($notes) {
            $application->notes = $notes;
        }

        $saved = $application->save();

        if ($saved) {
            // Send notification to seeker
            $this->notifyStatusChange($application, $previousStatus);

            Log::info('Application status updated', [
                'application_id' => $application->id,
                'from' => $previousStatus,
                'to' => $newStatus,
                'changed_by' => $changedBy?->id,
            ]);
        }

        return $saved;
    }

    /**
     * Withdraw an application.
     *
     * @param JobApplication $application
     * @param User $user
     * @return bool
     * @throws ValidationException
     */
    public function withdraw(JobApplication $application, User $user): bool
    {
        if (!$this->canWithdraw($application)) {
            throw ValidationException::withMessages([
                'application' => 'This application cannot be withdrawn.',
            ]);
        }

        return $this->updateStatus($application, 'withdrawn', $user);
    }

    /**
     * Get applications for a seeker with filters.
     *
     * @param Seeker $seeker
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSeekerApplications(
        Seeker $seeker,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $seeker->applications()
            ->with(['jobPosting.employer', 'employer']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('jobPosting', function ($jq) use ($search) {
                    $jq->where('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                })->orWhereHas('employer', function ($eq) use ($search) {
                    $eq->where('company_name', 'like', "%{$search}%");
                });
            });
        }

        return $query->latest('applied_at')->paginate($perPage);
    }

    /**
     * Get application statistics for a seeker.
     *
     * @param Seeker $seeker
     * @return array
     */
    public function getSeekerStats(Seeker $seeker): array
    {
        $applications = $seeker->applications;

        $stats = [
            'total' => (int) $applications->count(),
            'active' => (int) $applications->whereIn('status', self::ACTIVE_STATUSES)->count(),
            'hired' => (int) $applications->where('status', 'hired')->count(),
            'rejected' => (int) $applications->where('status', 'rejected')->count(),
            'withdrawn' => (int) $applications->where('status', 'withdrawn')->count(),
            'interviews_scheduled' => (int) $applications->where('status', 'interview_scheduled')->count(),
            'contracts_pending' => (int) $applications->where('status', 'contract_sent')->count(),
        ];

        // Calculate rates
        $stats['success_rate'] = $stats['total'] > 0
            ? round(($stats['hired'] / $stats['total']) * 100, 1)
            : 0.0;

        $stats['response_rate'] = $stats['total'] > 0
            ? round((($stats['total'] - $applications->where('status', 'applied')->count()) / $stats['total']) * 100, 1)
            : 0.0;

        return $stats;
    }

    /**
     * Get activity feed for a seeker.
     *
     * @param Seeker $seeker
     * @param int $limit
     * @return array
     */
    public function getActivityFeed(Seeker $seeker, int $limit = 50): array
    {
        $applications = $seeker->applications()
            ->with(['jobPosting.employer'])
            ->orderBy('status_changed_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $applications->map(function ($application) {
            $timestamp = $application->status_changed_at ?? $application->created_at;

            return [
                'id' => (int) $application->id,
                'type' => $this->getActivityType($application->status),
                'title' => $this->getActivityTitle($application),
                'context' => $this->getActivityContext($application),
                'status' => (string) $application->status,
                'status_label' => $this->getStatusLabel($application->status),
                'status_color' => $this->getStatusColor($application->status),
                'created_at' => $timestamp->toIso8601String(),
                'created_at_human' => $timestamp->diffForHumans(),
                'priority' => $this->getActivityPriority($application->status),
                'route' => $this->getActivityRoute($application),
                'related_id' => $this->getRelatedId($application),
                'is_completed' => in_array($application->status, self::TERMINAL_STATUSES),
                'cta_label' => $this->getCTALabel($application->status),
                'job' => [
                    'id' => (int) $application->job_posting_id,
                    'title' => (string) ($application->jobPosting->title ?? 'Unknown Job'),
                    'company' => (string) ($application->jobPosting->employer->company_name ?? 'Unknown Company'),
                ],
            ];
        })->toArray();
    }

    /**
     * Check if application can be withdrawn.
     *
     * @param JobApplication $application
     * @return bool
     */
    public function canWithdraw(JobApplication $application): bool
    {
        return in_array($application->status, self::ACTIVE_STATUSES);
    }

    /**
     * Validate job is accepting applications.
     *
     * @param JobPosting $job
     * @throws ValidationException
     */
    private function validateJobAcceptsApplications(JobPosting $job): void
    {
        if ($job->status !== 'active') {
            throw ValidationException::withMessages([
                'job' => 'This job is no longer accepting applications.',
            ]);
        }

        if ($job->deadline && $job->deadline->isPast()) {
            throw ValidationException::withMessages([
                'job' => 'The application deadline has passed.',
            ]);
        }
    }

    /**
     * Check for duplicate application.
     *
     * @param Seeker $seeker
     * @param JobPosting $job
     * @throws ValidationException
     */
    private function checkDuplicateApplication(Seeker $seeker, JobPosting $job): void
    {
        $exists = JobApplication::where('seeker_id', $seeker->id)
            ->where('job_posting_id', $job->id)
            ->whereNotIn('status', ['withdrawn'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'job' => 'You have already applied to this job.',
            ]);
        }
    }

    /**
     * Send notification about status change.
     *
     * @param JobApplication $application
     * @param string $previousStatus
     */
    private function notifyStatusChange(JobApplication $application, string $previousStatus): void
    {
        try {
            $seeker = $application->seeker;
            $user = $seeker?->user;

            if (!$user) {
                Log::warning('Cannot notify - no user found', [
                    'application_id' => $application->id,
                ]);
                return;
            }

            $application->load(['jobPosting.employer', 'employer']);
            $user->notify(new ApplicationStatusChanged($application, $previousStatus));

            Log::info('Status change notification sent', [
                'application_id' => $application->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send status notification', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get human-readable status label.
     *
     * @param string $status
     * @return string
     */
    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'applied' => 'Applied',
            'reviewed' => 'Under Review',
            'shortlisted' => 'Shortlisted',
            'interview_scheduled' => 'Interview Scheduled',
            'interviewed' => 'Interviewed',
            'contract_sent' => 'Contract Sent',
            'hired' => 'Hired',
            'rejected' => 'Not Selected',
            'withdrawn' => 'Withdrawn',
            default => ucfirst($status),
        };
    }

    /**
     * Get status color for UI.
     *
     * @param string $status
     * @return string
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'applied' => 'blue',
            'reviewed' => 'yellow',
            'shortlisted' => 'purple',
            'interview_scheduled' => 'orange',
            'interviewed' => 'indigo',
            'contract_sent' => 'cyan',
            'hired' => 'green',
            'rejected' => 'red',
            'withdrawn' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get next steps guidance for status.
     *
     * @param string $status
     * @return string
     */
    private function getNextSteps(string $status): string
    {
        return match ($status) {
            'applied' => 'Your application is being reviewed. You will be notified of any updates.',
            'reviewed' => 'The employer is reviewing your profile. Stay tuned for updates.',
            'shortlisted' => 'Congratulations! Prepare for a potential interview.',
            'interview_scheduled' => 'Review the interview details and prepare accordingly.',
            'interviewed' => 'The employer is evaluating candidates. Decision coming soon.',
            'contract_sent' => 'Review and sign your employment contract.',
            'hired' => 'Welcome aboard! Check your email for onboarding details.',
            'rejected' => 'Keep applying - your next opportunity is out there.',
            'withdrawn' => 'This application has been withdrawn.',
            default => 'Check back for updates.',
        };
    }

    /**
     * Get activity type for feed.
     *
     * @param string $status
     * @return string
     */
    private function getActivityType(string $status): string
    {
        return match ($status) {
            'applied' => 'application',
            'reviewed' => 'review',
            'shortlisted' => 'shortlist',
            'interview_scheduled', 'interviewed' => 'interview',
            'contract_sent' => 'contract',
            'hired' => 'offer',
            'rejected' => 'rejection',
            'withdrawn' => 'withdrawal',
            default => 'update',
        };
    }

    /**
     * Get activity title.
     *
     * @param JobApplication $application
     * @return string
     */
    private function getActivityTitle(JobApplication $application): string
    {
        $jobTitle = $application->jobPosting->title ?? 'Unknown Position';

        return match ($application->status) {
            'applied' => "Applied for {$jobTitle}",
            'reviewed' => 'Application under review',
            'shortlisted' => "You've been shortlisted!",
            'interview_scheduled' => 'Interview scheduled',
            'interviewed' => 'Interview completed',
            'contract_sent' => 'Contract ready for review',
            'hired' => "Congratulations! You're hired",
            'rejected' => 'Application not selected',
            'withdrawn' => 'Application withdrawn',
            default => 'Application updated',
        };
    }

    /**
     * Get activity context.
     *
     * @param JobApplication $application
     * @return string
     */
    private function getActivityContext(JobApplication $application): string
    {
        $company = $application->employer?->company_name
            ?? $application->jobPosting->employer->company_name
            ?? 'the company';

        return match ($application->status) {
            'interview_scheduled' => "Prepare for your interview with {$company}",
            'contract_sent' => 'Review and sign your employment contract',
            'hired' => "Welcome to the team at {$company}",
            'rejected' => 'Keep searching - your next opportunity is out there',
            'withdrawn' => 'You withdrew this application',
            default => "Track your application progress with {$company}",
        };
    }

    /**
     * Get activity priority.
     *
     * @param string $status
     * @return string
     */
    private function getActivityPriority(string $status): string
    {
        return match ($status) {
            'interview_scheduled', 'contract_sent' => 'high',
            'reviewed', 'shortlisted', 'interviewed' => 'medium',
            default => 'low',
        };
    }

    /**
     * Get activity route based on status.
     *
     * @param JobApplication $application
     * @return string
     */
    private function getActivityRoute(JobApplication $application): string
    {
        return match ($application->status) {
            'contract_sent' => "/seeker/contracts", // Will redirect to contract detail
            default => "/seeker/applications/{$application->id}",
        };
    }

    /**
     * Get related ID for navigation (contract ID for contract_sent status).
     *
     * @param JobApplication $application
     * @return int|null
     */
    private function getRelatedId(JobApplication $application): ?int
    {
        if ($application->status === 'contract_sent') {
            // Find the contract associated with this application
            $contract = \App\Models\Contract::where('job_application_id', $application->id)
                ->first();
            return $contract?->id;
        }
        
        return null;
    }

    /**
     * Get CTA label.
     *
     * @param string $status
     * @return string
     */
    private function getCTALabel(string $status): string
    {
        return match ($status) {
            'interview_scheduled' => 'View Details',
            'contract_sent' => 'Review Contract',
            'shortlist' => 'Prepare for Interview',
            'reviewed' => 'Check Status',
            'applied' => 'Track Progress',
            'hired' => 'View Offer',
            'rejected' => 'View Feedback',
            'withdrawn' => 'View Application',
            default => 'View Details',
        };
    }
}
