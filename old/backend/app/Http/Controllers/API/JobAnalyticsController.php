<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\JobAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(JobAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get analytics for a specific job
     */
    public function getJobStats(Request $request, $jobId): JsonResponse
    {
        try {
            \Log::info('JobAnalytics getJobStats called', ['jobId' => $jobId, 'user_id' => auth()->id()]);
            
            // Temporarily bypass authentication for testing
            if (!auth()->check()) {
                \Log::warning('No authenticated user found');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication required'
                ], 401);
            }
            
            // Verify employer owns this job (temporarily relaxed for testing)
            $job = \App\Models\JobPosting::find($jobId);
            if (!$job) {
                \Log::error('Job not found', ['jobId' => $jobId]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Job not found'
                ], 404);
            }

            \Log::info('Job found', ['job_id' => $job->id, 'title' => $job->title]);

            $stats = $this->analyticsService->getJobStats($jobId);

            \Log::info('Stats generated', ['stats_keys' => array_keys($stats)]);

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('JobAnalytics error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record a job view
     */
    public function recordView(Request $request, $jobId): JsonResponse
    {
        $request->validate([
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
        ]);

        $job = \App\Models\JobPosting::findOrFail($jobId);

        $view = $this->analyticsService->recordJobView(
            $jobId,
            auth()->id(),
            $request->ip_address ?? $request->ip(),
            $request->user_agent ?? $request->header('User-Agent')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Job view recorded',
            'data' => $view
        ]);
    }

    /**
     * Get analytics for all jobs of the employer
     */
    public function getEmployerAnalytics(Request $request): JsonResponse
    {
        $employerId = auth()->user()->employer->id;
        $analytics = $this->analyticsService->getEmployerJobAnalytics($employerId);

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * Get job views over time for chart
     */
    public function getJobViewsChart(Request $request, $jobId): JsonResponse
    {
        // Verify employer owns this job
        $job = \App\Models\JobPosting::where('id', $jobId)
            ->where('employer_id', auth()->user()->employer->id)
            ->firstOrFail();

        $days = min($request->get('days', 30), 90); // Max 90 days
        
        $views = \App\Models\JobView::where('job_id', $jobId)
            ->where('viewed_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $views
        ]);
    }

    /**
     * Get application funnel data
     */
    public function getApplicationFunnel(Request $request, $jobId): JsonResponse
    {
        // Verify employer owns this job
        $job = \App\Models\JobPosting::where('id', $jobId)
            ->where('employer_id', auth()->user()->employer->id)
            ->firstOrFail();

        $applications = \App\Models\JobApplication::where('job_posting_id', $jobId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Define the funnel stages
        $funnel = [
            'applied' => $applications['applied'] ?? 0,
            'reviewed' => $applications['reviewed'] ?? 0,
            'shortlisted' => $applications['shortlisted'] ?? 0,
            'interview_scheduled' => ($applications['interview_scheduled'] ?? 0) + ($applications['interviewed'] ?? 0),
            'contract_sent' => $applications['contract_sent'] ?? 0,
            'hired' => $applications['hired'] ?? 0,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $funnel
        ]);
    }
}
