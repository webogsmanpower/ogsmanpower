<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\User;
use App\Models\JobPosting;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AdminDashboardController
 * 
 * Provides dashboard statistics and overview data for the Admin Module.
 * The command center for system administrators.
 */
class AdminDashboardController extends Controller
{
    /**
     * Get dashboard overview statistics.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $stats = [
            // User counts
            'total_seekers' => User::where('role', 'seeker')->count(),
            'total_employers' => User::where('role', 'employer')->count(),
            'total_admins' => User::where('role', 'admin')->count(),
            
            // Employer verification stats (highlighted)
            'pending_verifications' => Employer::where('verification_status', 'pending')->count(),
            'verified_employers' => Employer::where('verification_status', 'verified')->count(),
            'rejected_employers' => Employer::where('verification_status', 'rejected')->count(),
            
            // Job stats
            'total_jobs' => JobPosting::count(),
            'active_jobs' => JobPosting::where('status', 'published')->count(),
            'draft_jobs' => JobPosting::where('status', 'draft')->count(),
            'closed_jobs' => JobPosting::where('status', 'closed')->count(),
            
            // Application stats
            'total_applications' => JobApplication::count(),
            'pending_applications' => JobApplication::where('status', 'pending')->count(),
        ];

        // Growth metrics (last 30 days)
        $thirtyDaysAgo = now()->subDays(30);
        $stats['new_seekers_30d'] = User::where('role', 'seeker')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        $stats['new_employers_30d'] = User::where('role', 'employer')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        $stats['new_jobs_30d'] = JobPosting::where('created_at', '>=', $thirtyDaysAgo)->count();

        return response()->json([
            'stats' => $stats,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get recent activity log.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentActivity(Request $request)
    {
        $limit = $request->get('limit', 20);

        // Recent employer registrations
        $recentEmployers = Employer::with('user:id,name,email')
            ->select('id', 'user_id', 'company_name', 'verification_status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($employer) {
                return [
                    'type' => 'employer_registration',
                    'id' => $employer->id,
                    'title' => "New Employer: {$employer->company_name}",
                    'description' => "Registered by {$employer->user->name}",
                    'status' => $employer->verification_status,
                    'created_at' => $employer->created_at->toIso8601String(),
                    'link' => "/admin/employers/{$employer->id}",
                ];
            });

        // Recent seeker registrations
        $recentSeekers = User::where('role', 'seeker')
            ->select('id', 'name', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'seeker_registration',
                    'id' => $user->id,
                    'title' => "New Seeker: {$user->name}",
                    'description' => $user->email,
                    'status' => null,
                    'created_at' => $user->created_at->toIso8601String(),
                    'link' => "/admin/seekers/{$user->id}",
                ];
            });

        // Recent job postings
        $recentJobs = JobPosting::with('employer:id,company_name')
            ->select('id', 'employer_id', 'title', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                return [
                    'type' => 'job_posting',
                    'id' => $job->id,
                    'title' => "New Job: {$job->title}",
                    'description' => "Posted by {$job->employer->company_name}",
                    'status' => $job->status,
                    'created_at' => $job->created_at->toIso8601String(),
                    'link' => "/admin/jobs/{$job->id}",
                ];
            });

        // Merge and sort by date
        $activity = collect()
            ->merge($recentEmployers)
            ->merge($recentSeekers)
            ->merge($recentJobs)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();

        return response()->json([
            'activity' => $activity,
        ]);
    }

    /**
     * Get chart data for dashboard visualizations.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chartData(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        // Daily registrations
        $dailyRegistrations = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('role'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date', 'role')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                return [
                    'seekers' => $items->where('role', 'seeker')->sum('count'),
                    'employers' => $items->where('role', 'employer')->sum('count'),
                ];
            });

        // Daily job postings
        $dailyJobs = JobPosting::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Verification status distribution
        $verificationDistribution = Employer::select('verification_status', DB::raw('COUNT(*) as count'))
            ->groupBy('verification_status')
            ->pluck('count', 'verification_status');

        return response()->json([
            'daily_registrations' => $dailyRegistrations,
            'daily_jobs' => $dailyJobs,
            'verification_distribution' => $verificationDistribution,
            'period_days' => $days,
        ]);
    }
}
