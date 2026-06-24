<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\JobMatchingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobMatchingController extends Controller
{
    private JobMatchingService $jobMatchingService;

    public function __construct(JobMatchingService $jobMatchingService)
    {
        $this->jobMatchingService = $jobMatchingService;
        $this->middleware('auth:api');
    }

    /**
     * Get matched jobs for the authenticated seeker
     */
    public function getMatchedJobs(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;
        
        if (!$seeker) {
            return response()->json([
                'message' => 'Seeker profile not found'
            ], 404);
        }

        $limit = min($request->get('limit', 20), 50); // Max 50 jobs
        $matchedJobs = $this->jobMatchingService->findMatchingJobsForSeeker($seeker, $limit);

        return response()->json([
            'data' => $matchedJobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company_name' => $job->company_name,
                    'location' => $job->location,
                    'job_type' => $job->job_type,
                    'industry' => $job->industry,
                    'salary_min' => $job->salary_min,
                    'salary_max' => $job->salary_max,
                    'description' => $job->description,
                    'requirements' => $job->requirements,
                    'application_deadline' => $job->application_deadline,
                    'created_at' => $job->created_at,
                    'match_score' => $this->calculateMatchScore($job, $seeker),
                ];
            }),
            'meta' => [
                'total' => $matchedJobs->count(),
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * Get job match statistics for seeker
     */
    public function getMatchStats(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;
        
        if (!$seeker) {
            return response()->json([
                'message' => 'Seeker profile not found'
            ], 404);
        }

        $preferences = $seeker->resume->job_preferences ?? [];
        $allJobs = \App\Models\JobPosting::where('status', 'active')
            ->where('application_deadline', '>=', now())
            ->get();
        
        $matchedJobs = $allJobs->filter(function ($job) use ($seeker) {
            return $this->jobMatchingService->isJobMatchForSeeker($job, $seeker);
        });

        return response()->json([
            'data' => [
                'total_active_jobs' => $allJobs->count(),
                'matched_jobs' => $matchedJobs->count(),
                'match_percentage' => $allJobs->count() > 0 
                    ? round(($matchedJobs->count() / $allJobs->count()) * 100, 2)
                    : 0,
                'preferences_summary' => [
                    'preferred_locations' => $preferences['preferred_locations'] ?? [],
                    'preferred_job_titles' => $preferences['preferred_job_titles'] ?? [],
                    'preferred_industries' => $preferences['preferred_industries'] ?? [],
                    'job_types' => $preferences['job_types'] ?? [],
                    'salary_expectations' => $preferences['salary_expectations'] ?? null,
                ],
                'recent_matches' => $matchedJobs->take(5)->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'company_name' => $job->company_name,
                        'location' => $job->location,
                        'posted_date' => $job->created_at->format('Y-m-d'),
                    ];
                }),
            ]
        ]);
    }

    /**
     * Calculate a simple match score for UI purposes
     */
    private function calculateMatchScore($job, $seeker): int
    {
        $preferences = $seeker->resume->job_preferences ?? [];
        $score = 0;
        $maxScore = 100;

        // Location match (40 points)
        if ($this->jobMatchingService->matchesLocation($job, $preferences)) {
            $score += 40;
        }

        // Job title match (25 points)
        if ($this->jobMatchingService->matchesJobTitles($job, $preferences)) {
            $score += 25;
        }

        // Industry match (20 points)
        if ($this->jobMatchingService->matchesIndustries($job, $preferences)) {
            $score += 20;
        }

        // Job type match (10 points)
        if ($this->jobMatchingService->matchesJobType($job, $preferences)) {
            $score += 10;
        }

        // Salary match (5 points)
        if ($this->jobMatchingService->matchesSalary($job, $preferences)) {
            $score += 5;
        }

        return min($score, $maxScore);
    }
}
