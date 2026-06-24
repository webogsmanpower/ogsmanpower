<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\JobApplication;
use App\Models\Seeker;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * ApplicationService
 * 
 * Business logic for job application management.
 */
class ApplicationService
{
    /**
     * Get applications for an employer with filters.
     */
    public function getApplicationsForEmployer(Employer $employer, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Include seeker.resume for complete candidate info display and contract to check if already sent
        $query = $employer->applications()->with(['seeker.user', 'seeker.resume', 'jobPosting', 'contract']);
        
        Log::debug('ApplicationService::getApplicationsForEmployer', [
            'employer_id' => $employer->id,
            'filters' => $filters,
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['job_id'])) {
            $query->where('job_posting_id', $filters['job_id']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['is_favorite']) && $filters['is_favorite']) {
            $query->where('is_favorite', true);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('seeker', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })->orWhereHas('seeker.user', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%");
            });
        }

        $result = $query->latest()->paginate($perPage);
        
        Log::debug('ApplicationService::getApplicationsForEmployer result', [
            'total' => $result->total(),
            'count' => $result->count(),
        ]);
        
        return $result;
    }

    /**
     * Get an application by ID for an employer.
     */
    public function getApplicationById(Employer $employer, int $id): ?JobApplication
    {
        return $employer->applications()->find($id);
    }

    /**
     * Update application status.
     */
    public function updateStatus(
        JobApplication $application,
        string $newStatus,
        User $changedBy = null,
        ?string $rejectionReason = null,
        ?string $rejectionFeedback = null,
        ?string $notes = null
    ): bool {
        $previousStatus = $application->status;
        Log::info("Updating status for application: {$application->id} from {$previousStatus} to {$newStatus}");
        
        $result = $application->updateStatus($newStatus, $changedBy, $rejectionReason);

        if ($result) {
            $updateData = [];
            
            if ($rejectionFeedback) {
                $updateData['rejection_feedback'] = $rejectionFeedback;
            }
            
            if ($notes) {
                $updateData['notes'] = $notes;
            }

            if (!empty($updateData)) {
                $application->update($updateData);
            }

            // Update job posting application count if hired or rejected
            if (in_array($newStatus, ['hired', 'rejected', 'withdrawn'])) {
                $jobPosting = $application->jobPosting;
                if ($jobPosting && method_exists($jobPosting, 'decrementApplications')) {
                    $jobPosting->decrementApplications();
                }
            }
            
            // Send notification to the seeker
            $this->notifySeeker($application, $previousStatus);
            
            Log::info("Application status updated successfully", [
                'application_id' => $application->id,
                'new_status' => $newStatus,
                'seeker_notified' => true,
            ]);
        }

        return $result;
    }

    /**
     * Notify seeker about application status change.
     */
    protected function notifySeeker(JobApplication $application, string $previousStatus): void
    {
        try {
            $seeker = $application->seeker;
            if (!$seeker) {
                Log::warning("No seeker found for application: {$application->id}");
                return;
            }

            $user = $seeker->user;
            if (!$user) {
                Log::warning("No user found for seeker: {$seeker->id}");
                return;
            }

            // Load relationships for notification
            $application->load(['jobPosting.employer', 'employer']);

            // Send notification
            $user->notify(new ApplicationStatusChanged($application, $previousStatus));
            
            Log::info("Notification sent to seeker", [
                'user_id' => $user->id,
                'application_id' => $application->id,
                'new_status' => $application->status,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send notification: " . $e->getMessage(), [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get pipeline statistics.
     */
    public function getPipelineStats(Employer $employer, ?int $jobId = null): array
    {
        $query = $employer->applications();

        if ($jobId) {
            $query->where('job_posting_id', $jobId);
        }

        $statuses = [
            'applied', 'reviewed', 'shortlisted', 'interview_scheduled',
            'interviewed', 'contract_sent', 'hired', 'rejected', 'withdrawn'
        ];

        $stats = [];
        foreach ($statuses as $status) {
            $stats[$status] = (clone $query)->where('status', $status)->count();
        }

        $stats['total'] = array_sum($stats);
        $stats['active'] = $stats['total'] - $stats['hired'] - $stats['rejected'] - $stats['withdrawn'];

        return $stats;
    }

    /**
     * Get applications for a seeker with filters.
     */
    public function getApplicationsForSeeker(Seeker $seeker, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $seeker->applications()->with(['jobPosting.employer', 'employer']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_favorite']) && $filters['is_favorite']) {
            $query->where('is_favorite', true);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('jobPosting', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            })->orWhereHas('employer', function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get an application by ID for a seeker.
     */
    public function getApplicationByIdForSeeker(Seeker $seeker, int $id): ?JobApplication
    {
        return $seeker->applications()->find($id);
    }

    /**
     * Get seeker's application activity feed.
     */
    public function getApplicationActivity(Seeker $seeker, int $limit = 50): array
    {
        $applications = $seeker->applications()
            ->with(['jobPosting.employer'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $activities = [];
        foreach ($applications as $application) {
            // Use status_changed_at if available, otherwise fallback to created_at
            $timestamp = $application->status_changed_at ?? $application->created_at;
            
            $activities[] = [
                'id' => $application->id,
                'type' => $this->getActivityType($application->status),
                'title' => $this->getActivityTitle($application),
                'context' => $this->getActivityContext($application),
                'status' => $application->status,
                'status_label' => $application->status_label,
                'created_at' => $timestamp->toIso8601String(),
                'created_at_human' => $timestamp->diffForHumans(),
                'priority' => $this->getActivityPriority($application->status),
                'route' => "/seeker/applications/{$application->id}",
                'is_completed' => in_array($application->status, ['hired', 'rejected', 'withdrawn']),
                'is_overdue' => false,
                'due_in_hours' => $this->getDueInHours($application->status),
                'due_label' => $this->getDueLabel($application->status),
                'cta_label' => $this->getCTALabel($application->status),
            ];
        }

        return $activities;
    }

    /**
     * Get seeker's application statistics.
     */
    public function getSeekerApplicationStats(Seeker $seeker): array
    {
        $applications = $seeker->applications;
        
        $stats = [
            'total' => $applications->count(),
            'active' => $applications->whereIn('status', ['applied', 'reviewed', 'shortlisted', 'interview_scheduled', 'interviewed', 'contract_sent'])->count(),
            'hired' => $applications->where('status', 'hired')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
            'withdrawn' => $applications->where('status', 'withdrawn')->count(),
            'interviews_scheduled' => $applications->where('status', 'interview_scheduled')->count(),
            'contracts_sent' => $applications->where('status', 'contract_sent')->count(),
            'favorites' => $applications->where('is_favorite', true)->count(),
        ];

        $stats['success_rate'] = $stats['total'] > 0 ? round(($stats['hired'] / $stats['total']) * 100, 1) : 0;
        $stats['response_rate'] = $stats['total'] > 0 ? round((($stats['total'] - $stats['rejected'] - $stats['withdrawn']) / $stats['total']) * 100, 1) : 0;

        return $stats;
    }

    /**
     * Get activity type based on status.
     */
    private function getActivityType(string $status): string
    {
        return match($status) {
            'applied' => 'application',
            'reviewed' => 'review',
            'shortlisted' => 'shortlist',
            'interview_scheduled' => 'interview',
            'interviewed' => 'interview',
            'contract_sent' => 'contract',
            'hired' => 'offer',
            'rejected' => 'rejection',
            'withdrawn' => 'withdrawal',
            default => 'update',
        };
    }

    /**
     * Get activity title.
     */
    private function getActivityTitle(JobApplication $application): string
    {
        return match($application->status) {
            'applied' => "Application submitted for {$application->jobPosting->title}",
            'reviewed' => "Your application is being reviewed",
            'shortlisted' => "You've been shortlisted!",
            'interview_scheduled' => "Interview scheduled",
            'interviewed' => "Interview completed",
            'contract_sent' => "Contract sent for review",
            'hired' => "Congratulations! You're hired",
            'rejected' => "Application not selected",
            'withdrawn' => "Application withdrawn",
            default => "Application status updated",
        };
    }

    /**
     * Get activity context.
     */
    private function getActivityContext(JobApplication $application): string
    {
        $company = $application->employer?->company_name ?? $application->jobPosting->employer->company_name;
        
        return match($application->status) {
            'interview_scheduled' => "Prepare for your interview with {$company}",
            'contract_sent' => "Review and sign your employment contract",
            'hired' => "Welcome to the team at {$company}",
            'rejected' => "Keep searching - your next opportunity is out there",
            'withdrawn' => "You withdrew this application",
            default => "Track your application progress with {$company}",
        };
    }

    /**
     * Get activity priority.
     */
    private function getActivityPriority(string $status): string
    {
        return match($status) {
            'interview_scheduled', 'contract_sent' => 'High',
            'reviewed', 'shortlisted', 'interviewed' => 'Medium',
            'applied', 'hired', 'rejected', 'withdrawn' => 'Low',
            default => 'Low',
        };
    }

    /**
     * Get due in hours.
     */
    private function getDueInHours(string $status): int
    {
        return match($status) {
            'interview_scheduled' => 48,
            'contract_sent' => 72,
            'shortlisted' => 168,
            'reviewed' => 240,
            default => 0,
        };
    }

    /**
     * Get due label.
     */
    private function getDueLabel(string $status): string
    {
        $hours = $this->getDueInHours($status);
        
        if ($hours === 0) {
            return match($status) {
                'hired' => 'Completed',
                'rejected' => 'Closed',
                'withdrawn' => 'Closed',
                'applied' => 'Submitted',
                default => 'No action needed',
            };
        }

        if ($hours <= 48) {
            return "Due in {$hours} hours";
        } elseif ($hours <= 168) {
            $days = round($hours / 24);
            return "Due in {$days} days";
        } else {
            return "Flexible";
        }
    }

    /**
     * Get CTA label.
     */
    private function getCTALabel(string $status): string
    {
        return match($status) {
            'interview_scheduled' => 'View details',
            'contract_sent' => 'Review contract',
            'shortlisted' => 'Prepare for interview',
            'reviewed' => 'Check status',
            'applied' => 'Track progress',
            'hired' => 'View offer',
            'rejected' => 'View feedback',
            'withdrawn' => 'View application',
            default => 'View details',
        };
    }
}
