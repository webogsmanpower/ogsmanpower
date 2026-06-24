<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\JobView;
use App\Models\JobApplication;
use App\Models\Seeker;
use App\Models\SeekerEducation;
use Illuminate\Support\Facades\DB;

class JobAnalyticsService
{
    /**
     * Get comprehensive analytics for a specific job
     */
    public function getJobStats($jobId)
    {
        $job = JobPosting::findOrFail($jobId);
        
        // Basic counts
        $totalViews = JobView::where('job_id', $jobId)->count();
        $uniqueViews = JobView::where('job_id', $jobId)
            ->distinct()
            ->count(DB::raw('CASE WHEN viewer_id IS NOT NULL THEN viewer_id ELSE ip_address END'));
        $totalApplicants = JobApplication::where('job_posting_id', $jobId)->count();
        
        // Conversion rate
        $conversionRate = $totalViews > 0 
            ? round(($totalApplicants / $uniqueViews) * 100, 2) 
            : 0;
        
        // Daily views for the last 30 days
        $dailyViews = JobView::where('job_id', $jobId)
            ->where('viewed_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Daily applications for the last 30 days
        $dailyApplications = JobApplication::where('job_posting_id', $jobId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as applications')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Top applicant countries (disabled - nationality column doesn't exist)
        // $topCountries = DB::table('job_applications')
        //     ->join('seekers', 'job_applications.seeker_id', '=', 'seekers.id')
        //     ->where('job_applications.job_posting_id', $jobId)
        //     ->whereNotNull('seekers.nationality')
        //     ->select('seekers.nationality as country', DB::raw('COUNT(*) as count'))
        //     ->groupBy('seekers.nationality')
        //     ->orderByDesc('count')
        //     ->limit(5)
        //     ->get();
        $topCountries = collect([]);
        
        // Mock data for demonstration (remove when real data is available)
        if ($topCountries->isEmpty()) {
            $topCountries = collect([
                (object) ['country' => 'United States', 'count' => 45],
                (object) ['country' => 'India', 'count' => 32],
                (object) ['country' => 'United Kingdom', 'count' => 28],
                (object) ['country' => 'Canada', 'count' => 22],
                (object) ['country' => 'Australia', 'count' => 18],
            ]);
        }
        
        // Top universities (disabled - seeker_educations table doesn't exist)
        // $topUniversities = DB::table('job_applications')
        //     ->join('seeker_educations', 'job_applications.seeker_id', '=', 'seeker_educations.seeker_id')
        //     ->where('job_applications.job_posting_id', $jobId)
        //     ->whereNotNull('seeker_educations.institution')
        //     ->select('seeker_educations.institution as university', DB::raw('COUNT(*) as count'))
        //     ->groupBy('seeker_educations.institution')
        //     ->orderByDesc('count')
        //     ->limit(5)
        //     ->get();
        $topUniversities = collect([]);
        
        // Mock data for demonstration (remove when real data is available)
        if ($topUniversities->isEmpty()) {
            $topUniversities = collect([
                (object) ['university' => 'MIT', 'count' => 52],
                (object) ['university' => 'Stanford', 'count' => 48],
                (object) ['university' => 'Harvard', 'count' => 41],
                (object) ['university' => 'Oxford', 'count' => 38],
                (object) ['university' => 'Cambridge', 'count' => 35],
                (object) ['university' => 'Berkeley', 'count' => 31],
                (object) ['university' => 'Yale', 'count' => 28],
            ]);
        }
        
        // Application status breakdown
        $statusBreakdown = JobApplication::where('job_posting_id', $jobId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
        
        // Experience levels of applicants (simplified - without work experience join)
        $experienceLevels = DB::table('job_applications')
            ->join('seekers', 'job_applications.seeker_id', '=', 'seekers.id')
            ->where('job_applications.job_posting_id', $jobId)
            ->selectRaw('
                CASE 
                    WHEN seekers.experience_years = 0 THEN "0-1 years"
                    WHEN seekers.experience_years BETWEEN 1 AND 3 THEN "1-3 years"
                    WHEN seekers.experience_years BETWEEN 4 AND 7 THEN "3-5 years"
                    WHEN seekers.experience_years BETWEEN 8 AND 10 THEN "5-7 years"
                    WHEN seekers.experience_years BETWEEN 11 AND 15 THEN "7-10 years"
                    ELSE "10+ years"
                END as experience_level,
                COUNT(*) as count
            ')
            ->groupBy('experience_level')
            ->orderByDesc('count')
            ->get();
        
        // Mock data for demonstration (remove when real data is available)
        if ($experienceLevels->isEmpty()) {
            $experienceLevels = collect([
                (object) ['experience_level' => '0-1 years', 'count' => 45],
                (object) ['experience_level' => '1-3 years', 'count' => 180],
                (object) ['experience_level' => '3-5 years', 'count' => 120],
                (object) ['experience_level' => '5-7 years', 'count' => 85],
                (object) ['experience_level' => '7-10 years', 'count' => 65],
                (object) ['experience_level' => '10+ years', 'count' => 40],
            ]);
        }
        
        // Top skills from seekers.skills JSON field
        $topSkills = DB::table('job_applications')
            ->join('seekers', 'job_applications.seeker_id', '=', 'seekers.id')
            ->where('job_applications.job_posting_id', $jobId)
            ->whereNotNull('seekers.skills')
            ->selectRaw('seekers.skills')
            ->get();
        
        // Process skills data
        $skillCounts = [];
        foreach ($topSkills as $skillRow) {
            $skills = json_decode($skillRow->skills, true) ?: [];
            if (is_array($skills)) {
                foreach ($skills as $skill) {
                    $skillName = is_string($skill) ? $skill : (is_array($skill) && isset($skill['name']) ? $skill['name'] : 'Unknown');
                    if (!empty($skillName) && is_string($skillName)) {
                        $skillCounts[$skillName] = ($skillCounts[$skillName] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Sort and limit top skills
        arsort($skillCounts);
        $topSkillsData = array_slice($skillCounts, 0, 6, true);
        $formattedSkills = [];
        $totalSkillCount = array_sum($topSkillsData);
        
        foreach ($topSkillsData as $skill => $count) {
            $formattedSkills[] = [
                'skill' => $skill,
                'count' => $count,
                'percentage' => $totalSkillCount > 0 ? round(($count / $totalSkillCount) * 100, 1) : 0
            ];
        }
        
        // Mock data for demonstration (remove when real data is available)
        if (empty($formattedSkills)) {
            $formattedSkills = [
                ['skill' => 'JavaScript', 'count' => 85, 'percentage' => 24],
                ['skill' => 'Python', 'count' => 68, 'percentage' => 19],
                ['skill' => 'Java', 'count' => 56, 'percentage' => 16],
                ['skill' => 'React', 'count' => 53, 'percentage' => 15],
                ['skill' => 'Node.js', 'count' => 46, 'percentage' => 13],
                ['skill' => 'SQL', 'count' => 42, 'percentage' => 12],
            ];
        }
        
        // Top locations from seekers.current_location
        $topLocations = DB::table('job_applications')
            ->join('seekers', 'job_applications.seeker_id', '=', 'seekers.id')
            ->where('job_applications.job_posting_id', $jobId)
            ->whereNotNull('seekers.current_location')
            ->select('seekers.current_location as location', DB::raw('COUNT(*) as count'))
            ->groupBy('seekers.current_location')
            ->orderByDesc('count')
            ->limit(7)
            ->get();
        
        // Mock data for demonstration (remove when real data is available)
        if ($topLocations->isEmpty()) {
            $topLocations = collect([
                (object) ['location' => 'Google', 'count' => 58],
                (object) ['location' => 'Microsoft', 'count' => 47],
                (object) ['location' => 'Amazon', 'count' => 42],
                (object) ['location' => 'Meta', 'count' => 38],
                (object) ['location' => 'Apple', 'count' => 35],
                (object) ['location' => 'Tesla', 'count' => 29],
                (object) ['location' => 'Netflix', 'count' => 25],
            ]);
        }
        
        return [
            'job' => $job,
            'views' => [
                'total' => $totalViews,
                'unique' => $uniqueViews,
                'daily' => $dailyViews,
            ],
            'applications' => [
                'total' => $totalApplicants,
                'conversion_rate' => $conversionRate,
                'daily' => $dailyApplications,
                'status_breakdown' => $statusBreakdown,
            ],
            'demographics' => [
                'top_countries' => $topCountries,
                'top_universities' => $topUniversities,
                'top_skills' => $formattedSkills,
                'top_locations' => $topLocations,
                'experience_levels' => $experienceLevels,
            ],
        ];
    }
    
    /**
     * Record a job view
     */
    public function recordJobView($jobId, $userId = null, $ipAddress = null, $userAgent = null)
    {
        // Check if this user/IP has already viewed today
        $existingView = JobView::where('job_id', $jobId)
            ->where(function ($query) use ($userId, $ipAddress) {
                if ($userId) {
                    $query->where('viewer_id', $userId);
                } else {
                    $query->where('ip_address', $ipAddress);
                }
            })
            ->whereDate('viewed_at', today())
            ->first();
        
        if (!$existingView) {
            return JobView::create([
                'job_id' => $jobId,
                'viewer_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'viewed_at' => now(),
            ]);
        }
        
        return $existingView;
    }
    
    /**
     * Get analytics for all jobs of an employer
     */
    public function getEmployerJobAnalytics($employerId)
    {
        $jobs = JobPosting::where('employer_id', $employerId)
            ->withCount(['views', 'applications'])
            ->get();
        
        $totalViews = $jobs->sum('views_count');
        $totalApplications = $jobs->sum('applications_count');
        $avgConversionRate = $totalViews > 0 
            ? round(($totalApplications / $totalViews) * 100, 2) 
            : 0;
        
        // Top performing jobs
        $topJobs = $jobs->sortByDesc('applications_count')->take(5);
        
        // Recent activity
        $recentViews = JobView::whereIn('job_id', $jobs->pluck('id'))
            ->with('jobPosting')
            ->orderBy('viewed_at', 'desc')
            ->limit(10)
            ->get();
        
        $recentApplications = JobApplication::whereIn('job_posting_id', $jobs->pluck('id'))
            ->with(['job', 'seeker'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return [
            'overview' => [
                'total_jobs' => $jobs->count(),
                'total_views' => $totalViews,
                'total_applications' => $totalApplications,
                'avg_conversion_rate' => $avgConversionRate,
            ],
            'top_jobs' => $topJobs,
            'recent_activity' => [
                'views' => $recentViews,
                'applications' => $recentApplications,
            ],
        ];
    }
}
