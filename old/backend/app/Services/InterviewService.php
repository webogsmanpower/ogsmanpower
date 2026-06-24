<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * InterviewService
 * 
 * Business logic for interview scheduling and management.
 */
class InterviewService
{
    /**
     * Get interviews for an employer with filters.
     */
    public function getInterviewsForEmployer(Employer $employer, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $employer->interviews()->with(['seeker.user', 'jobApplication.jobPosting']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['application_id'])) {
            $query->where('job_application_id', $filters['application_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('scheduled_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('scheduled_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('scheduled_at', 'asc')->paginate($perPage);
    }

    /**
     * Get upcoming interviews.
     */
    public function getUpcomingInterviews(Employer $employer, int $limit = 10): Collection
    {
        return $employer->interviews()
            ->with(['seeker.user', 'jobApplication.jobPosting'])
            ->upcoming()
            ->orderBy('scheduled_at', 'asc')
            ->take($limit)
            ->get();
    }

    /**
     * Get today's interviews.
     */
    public function getTodayInterviews(Employer $employer): Collection
    {
        return $employer->interviews()
            ->with(['seeker.user', 'jobApplication.jobPosting'])
            ->today()
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get an interview by ID for an employer.
     */
    public function getInterviewById(Employer $employer, int $id): ?Interview
    {
        return $employer->interviews()->find($id);
    }

    /**
     * Schedule a new interview.
     */
    public function scheduleInterview(Employer $employer, User $user, array $data): ?Interview
    {
        // Verify the application belongs to this employer
        $application = JobApplication::where('id', $data['job_application_id'])
            ->where('employer_id', $employer->id)
            ->first();

        if (!$application) {
            return null;
        }

        // Determine round number
        $existingInterviews = Interview::where('job_application_id', $application->id)->count();
        $roundNumber = $data['round_number'] ?? ($existingInterviews + 1);

        $interview = Interview::create([
            'job_application_id' => $application->id,
            'employer_id' => $employer->id,
            'seeker_id' => $application->seeker_id,
            'scheduled_by' => $user->id,
            'round_number' => $roundNumber,
            'interview_type' => $data['interview_type'],
            'title' => $data['title'] ?? "Interview Round {$roundNumber}",
            'scheduled_at' => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            'timezone' => $data['timezone'] ?? 'UTC',
            'location' => $data['location'] ?? null,
            'meeting_link' => $data['meeting_link'] ?? null,
            'meeting_id' => $data['meeting_id'] ?? null,
            'meeting_password' => $data['meeting_password'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'interviewers' => $data['interviewers'] ?? null,
            'notes' => $data['notes'] ?? null,
            'questions' => $data['questions'] ?? null,
        ]);

        // Update application status if not already at interview stage
        if (!in_array($application->status, ['interview_scheduled', 'interviewed', 'contract_sent', 'hired'])) {
            $application->updateStatus('interview_scheduled', $user);
        }

        return $interview;
    }

    /**
     * Update an interview.
     */
    public function updateInterview(Interview $interview, array $data): Interview
    {
        // If rescheduling, update status
        if (isset($data['scheduled_at']) && $data['scheduled_at'] !== $interview->scheduled_at->toDateTimeString()) {
            $data['status'] = 'rescheduled';
        }

        $interview->update($data);
        return $interview->fresh();
    }

    /**
     * Complete an interview with feedback.
     */
    public function completeInterview(Interview $interview, array $data): Interview
    {
        $interview->update([
            'status' => 'completed',
            'feedback' => $data['feedback'] ?? null,
            'feedback_summary' => $data['feedback_summary'] ?? null,
            'rating' => $data['rating'] ?? null,
            'outcome' => $data['outcome'],
            'recommendation' => $data['recommendation'] ?? null,
        ]);

        // Update application status
        $application = $interview->jobApplication;
        if ($application->status === 'interview_scheduled') {
            $application->updateStatus('interviewed');
        }

        return $interview->fresh();
    }

    /**
     * Get upcoming interviews for a seeker.
     */
    public function getUpcomingInterviewsForSeeker($seeker): Collection
    {
        return $seeker->interviews()
            ->with(['employer.user', 'jobApplication.jobPosting'])
            ->where('scheduled_at', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get past interviews for a seeker.
     */
    public function getPastInterviewsForSeeker($seeker): Collection
    {
        return $seeker->interviews()
            ->with(['employer.user', 'jobApplication.jobPosting'])
            ->where(function ($query) {
                $query->where('scheduled_at', '<=', now())
                      ->orWhere('status', 'completed')
                      ->orWhere('status', 'cancelled');
            })
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    /**
     * Get a specific interview for a seeker.
     */
    public function getInterviewForSeeker($seeker, int $id): ?Interview
    {
        return $seeker->interviews()
            ->with(['employer.user', 'jobApplication.jobPosting'])
            ->where('id', $id)
            ->first();
    }
}
