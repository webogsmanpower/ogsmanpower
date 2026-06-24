<?php

namespace App\Observers;

use App\Models\JobPosting;
use App\Services\JobMatchingService;
use Illuminate\Support\Facades\Log;

class JobObserver
{
    private JobMatchingService $jobMatchingService;

    public function __construct(JobMatchingService $jobMatchingService)
    {
        $this->jobMatchingService = $jobMatchingService;
    }

    /**
     * Handle the Job "created" event.
     */
    public function created(JobPosting $job): void
    {
        // Only process active jobs
        if ($job->status !== 'active') {
            return;
        }

        try {
            // Find matching seekers
            $matchedSeekers = $this->jobMatchingService->findMatchingSeekers($job);
            
            if ($matchedSeekers->isNotEmpty()) {
                // Send notifications to matched seekers
                $this->jobMatchingService->sendJobMatchNotifications($job, $matchedSeekers);
                
                Log::info('Job matching completed', [
                    'job_id' => $job->id,
                    'job_title' => $job->title,
                    'matched_seekers_count' => $matchedSeekers->count(),
                    'seeker_ids' => $matchedSeekers->pluck('id')->toArray()
                ]);
            } else {
                Log::info('No matching seekers found for job', [
                    'job_id' => $job->id,
                    'job_title' => $job->title
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Job matching failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Job "updated" event.
     */
    public function updated(JobPosting $job): void
    {
        // If job status changed to active, trigger matching
        if ($job->wasChanged('status') && $job->status === 'active') {
            $this->created($job);
        }
    }
}
