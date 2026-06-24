<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\Seeker;
use App\Models\SeekerResume;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * JobMatchingService - Handles intelligent job-seeker matching
 * 
 * Features:
 * - Multi-location matching (cities and countries)
 * - Job title matching
 * - Industry matching
 * - Job type matching
 * - Salary range matching
 * - Notification system integration
 */
class JobMatchingService
{
    /**
     * Find matching seekers for a newly posted job
     */
    public function findMatchingSeekers(JobPosting $job): Collection
    {
        return Seeker::with(['resume', 'user'])
            ->whereHas('resume')
            ->where('status', 'active')
            ->get()
            ->filter(function (Seeker $seeker) use ($job) {
                return $this->isJobMatchForSeeker($job, $seeker);
            });
    }

    /**
     * Check if a job matches a seeker's preferences
     */
    public function isJobMatchForSeeker(JobPosting $job, Seeker $seeker): bool
    {
        $preferences = $seeker->resume->job_preferences ?? [];
        
        // Check if seeker has any preferences set
        if (empty($preferences)) {
            return false;
        }

        // Location matching (most important)
        if (!$this->matchesLocation($job, $preferences)) {
            return false;
        }

        // Job title matching
        if (!$this->matchesJobTitles($job, $preferences)) {
            return false;
        }

        // Industry matching
        if (!$this->matchesIndustries($job, $preferences)) {
            return false;
        }

        // Job type matching
        if (!$this->matchesJobType($job, $preferences)) {
            return false;
        }

        // Salary matching (optional - if seeker specified salary expectations)
        if (!$this->matchesSalary($job, $preferences)) {
            return false;
        }

        return true;
    }

    /**
     * Check if job location matches seeker's preferred locations
     */
    private function matchesLocation(JobPosting $job, array $preferences): bool
    {
        $jobLocation = strtolower($job->location ?? '');
        $preferredLocations = $preferences['preferred_locations'] ?? [];
        
        if (empty($preferredLocations)) {
            return false;
        }

        // Normalize preferred locations
        $normalizedPreferred = array_map('strtolower', $preferredLocations);

        // Check for exact location match
        if (in_array($jobLocation, $normalizedPreferred)) {
            return true;
        }

        // Check for country-level matches
        foreach ($normalizedPreferred as $location) {
            // If preferred location is a country, check if job location is in that country
            if ($this->isCountryMatch($jobLocation, $location)) {
                return true;
            }
            
            // If job location contains preferred location (partial match)
            if (str_contains($jobLocation, $location) || str_contains($location, $jobLocation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if job titles match seeker's preferences
     */
    private function matchesJobTitles(JobPosting $job, array $preferences): bool
    {
        $jobTitle = strtolower($job->title ?? '');
        $preferredTitles = $preferences['preferred_job_titles'] ?? [];
        
        if (empty($preferredTitles)) {
            return false;
        }

        $normalizedTitles = array_map('strtolower', $preferredTitles);

        // Exact match
        if (in_array($jobTitle, $normalizedTitles)) {
            return true;
        }

        // Partial match (contains)
        foreach ($normalizedTitles as $title) {
            if (str_contains($jobTitle, $title) || str_contains($title, $jobTitle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if job industry matches seeker's preferences
     */
    private function matchesIndustries(JobPosting $job, array $preferences): bool
    {
        $jobIndustry = strtolower($job->industry ?? '');
        $preferredIndustries = $preferences['preferred_industries'] ?? [];
        
        if (empty($preferredIndustries)) {
            return false;
        }

        $normalizedIndustries = array_map('strtolower', $preferredIndustries);

        // Exact match
        if (in_array($jobIndustry, $normalizedIndustries)) {
            return true;
        }

        // Partial match
        foreach ($normalizedIndustries as $industry) {
            if (str_contains($jobIndustry, $industry) || str_contains($industry, $jobIndustry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if job type matches seeker's preferences
     */
    private function matchesJobType(JobPosting $job, array $preferences): bool
    {
        $jobType = strtolower($job->job_type ?? '');
        $preferredTypes = $preferences['job_types'] ?? [];
        
        if (empty($preferredTypes)) {
            return true; // If no preference, don't filter by job type
        }

        // Handle both string and array formats for job_types
        if (is_string($preferredTypes)) {
            $preferredTypes = [$preferredTypes];
        }

        $normalizedTypes = array_map('strtolower', (array) $preferredTypes);

        return in_array($jobType, $normalizedTypes);
    }

    /**
     * Check if salary matches seeker's expectations
     */
    private function matchesSalary(JobPosting $job, array $preferences): bool
    {
        $salaryExpectations = $preferences['salary_expectations'] ?? '';
        
        if (empty($salaryExpectations)) {
            return true; // If no salary preference, don't filter
        }

        $jobSalary = $job->salary_min ?? 0;
        
        // Extract salary range from expectations (e.g., "$80,000 - $100,000")
        if (preg_match('/\$?([\d,]+)\s*-\s*\$?([\d,]+)/', $salaryExpectations, $matches)) {
            $minExpectation = (int) str_replace(',', '', $matches[1]);
            $maxExpectation = (int) str_replace(',', '', $matches[2]);
            
            return $jobSalary >= $minExpectation && $jobSalary <= $maxExpectation;
        }

        // Extract single salary number
        if (preg_match('/\$?([\d,]+)/', $salaryExpectations, $matches)) {
            $expectedSalary = (int) str_replace(',', '', $matches[1]);
            return $jobSalary >= $expectedSalary * 0.8; // Allow 20% flexibility
        }

        return true;
    }

    /**
     * Check if location matches country
     */
    private function isCountryMatch(string $jobLocation, string $preferredLocation): bool
    {
        $countryMappings = [
            'united arab emirates' => ['dubai', 'abu dhabi', 'sharjah', 'ajman', 'umm al quwain', 'ras al khaimah', 'fujairah'],
            'uae' => ['dubai', 'abu dhabi', 'sharjah', 'ajman', 'umm al quwain', 'ras al khaimah', 'fujairah'],
            'saudi arabia' => ['riyadh', 'jeddah', 'mecca', 'medina', 'dammam'],
            'qatar' => ['doha', 'al wakrah', 'al rayyan'],
            'kuwait' => ['kuwait city', 'hawalli', 'sabah as salim'],
            'bahrain' => ['manama', 'muharraq', 'riffa'],
            'oman' => ['muscat', 'salalah', 'sohar'],
        ];

        $preferredLower = strtolower($preferredLocation);
        
        if (isset($countryMappings[$preferredLower])) {
            return in_array($jobLocation, $countryMappings[$preferredLower]);
        }

        // Reverse mapping - check if job location is a city and preferred is the country
        foreach ($countryMappings as $country => $cities) {
            if (in_array($jobLocation, $cities) && $preferredLower === $country) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send job match notifications to seekers
     */
    public function sendJobMatchNotifications(JobPosting $job, Collection $matchedSeekers): void
    {
        foreach ($matchedSeekers as $seeker) {
            try {
                $seeker->user->notify(new \App\Notifications\JobMatchNotification($job, $seeker));
                
                Log::info('Job match notification sent', [
                    'job_id' => $job->id,
                    'seeker_id' => $seeker->id,
                    'user_id' => $seeker->user->id,
                    'job_title' => $job->title,
                    'job_location' => $job->location
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send job match notification', [
                    'job_id' => $job->id,
                    'seeker_id' => $seeker->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Find matching jobs for a seeker
     */
    public function findMatchingJobsForSeeker(Seeker $seeker, int $limit = 20): Collection
    {
        return JobPosting::where('status', 'active')
            ->where('application_deadline', '>=', now())
            ->get()
            ->filter(function (JobPosting $job) use ($seeker) {
                return $this->isJobMatchForSeeker($job, $seeker);
            })
            ->take($limit);
    }

    /**
     * Get job match statistics
     */
    public function getJobMatchStats(JobPosting $job): array
    {
        $matchedSeekers = $this->findMatchingSeekers($job);
        
        return [
            'total_seekers' => Seeker::where('status', 'active')->count(),
            'matched_seekers' => $matchedSeekers->count(),
            'match_percentage' => $matchedSeekers->count() > 0 
                ? round(($matchedSeekers->count() / Seeker::where('status', 'active')->count()) * 100, 2)
                : 0,
            'top_locations' => $matchedSeekers->pluck('resume.job_preferences.preferred_locations')
                ->flatten()
                ->groupBy(fn($location) => $location)
                ->map(fn($group) => $group->count())
                ->sortDesc()
                ->take(5)
                ->toArray(),
        ];
    }
}
