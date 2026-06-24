<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * JobPostingService
 * 
 * Business logic for job posting operations.
 */
class JobPostingService
{
    /**
     * Get jobs for an employer with filters.
     */
    public function getJobsForEmployer(Employer $employer, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $employer->jobPostings()->with(['createdBy']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['job_type'])) {
            $query->where('job_type', $filters['job_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a job by ID for an employer.
     */
    public function getJobById(Employer $employer, int $id): ?JobPosting
    {
        return $employer->jobPostings()->find($id);
    }

    /**
     * Create a new job posting.
     */
    public function createJob(Employer $employer, User $user, array $data): JobPosting
    {
        $data['employer_id'] = $employer->id;
        $data['created_by'] = $user->id;

        // Handle screening questions and assessments separately
        $screeningQuestions = $data['screening_questions'] ?? null;
        $assessmentIds = $data['assessment_ids'] ?? null;
        
        // Remove from main data array as they're handled separately
        unset($data['screening_questions'], $data['assessment_ids']);

        // Auto-publish if status is published
        if (($data['status'] ?? 'draft') === 'published') {
            $data['published_at'] = now();
        }

        $job = JobPosting::create($data);
        
        // Handle screening questions if provided
        if ($screeningQuestions) {
            // This would need a ScreeningQuestion model and relationship
            // For now, store as JSON in the job posting
            $job->update(['screening_questions' => $screeningQuestions]);
        }
        
        // Handle assessment attachments if provided
        if ($assessmentIds && !empty($assessmentIds)) {
            // This would need a job_assessments pivot table
            // For now, store as JSON in the job posting
            $job->update(['assessment_ids' => $assessmentIds]);
        }

        return $job;
    }

    /**
     * Update a job posting.
     */
    public function updateJob(JobPosting $job, array $data): JobPosting
    {
        $job->update($data);
        return $job->fresh();
    }

    /**
     * Delete a job posting.
     */
    public function deleteJob(JobPosting $job): bool
    {
        return $job->delete();
    }

    /**
     * Update job status.
     */
    public function updateJobStatus(JobPosting $job, string $status): JobPosting
    {
        $updateData = ['status' => $status];

        if ($status === 'published' && !$job->published_at) {
            $updateData['published_at'] = now();
        }

        if (in_array($status, ['closed', 'filled'])) {
            $updateData['closed_at'] = now();
        }

        $job->update($updateData);
        return $job->fresh();
    }

    /**
     * Get job statistics for employer.
     */
    public function getJobStats(Employer $employer): array
    {
        return [
            'total' => $employer->jobPostings()->count(),
            'draft' => $employer->jobPostings()->where('status', 'draft')->count(),
            'published' => $employer->jobPostings()->where('status', 'published')->count(),
            'paused' => $employer->jobPostings()->where('status', 'paused')->count(),
            'closed' => $employer->jobPostings()->where('status', 'closed')->count(),
            'filled' => $employer->jobPostings()->where('status', 'filled')->count(),
        ];
    }
}
