<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use App\Models\SavedJob;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * PublicJobController
 * 
 * Handles public job listing endpoints for seekers/candidates.
 * These endpoints return only published, active jobs.
 */
class PublicJobController extends Controller
{
    /**
     * List all published jobs for seekers.
     * 
     * Filters:
     * - search: Search in title/description
     * - job_type: full_time, part_time, contract, temporary, internship
     * - experience_level: entry, junior, mid, senior, executive
     * - location_country: Country code (e.g., QA, AE, SA)
     * - is_remote: boolean
     * - salary_min: Minimum salary filter
     * - salary_max: Maximum salary filter
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $seekerId = null;
        
        // Get seeker ID if user is authenticated
        if ($user && $user->seeker) {
            $seekerId = $user->seeker->id;
        }
        
        // Debug logging
        \Log::info('PublicJobController: User authenticated: ' . ($user ? 'yes' : 'no'));
        \Log::info('PublicJobController: Seeker ID: ' . $seekerId);
        
        $query = JobPosting::with(['employer'])
            ->active() // Uses scope: published + not expired
            ->latest('published_at');

        if ($user) {
            $query->addSelect([
                'is_saved' => SavedJob::selectRaw('1')
                    ->whereColumn('saved_jobs.job_posting_id', 'job_postings.id')
                    ->where('saved_jobs.user_id', $user->id)
                    ->limit(1)
            ]);
        }
        
        // Add has_applied flag if user is authenticated
        if ($seekerId) {
            $query->withCount(['applications as has_applied' => function ($q) use ($seekerId) {
                $q->where('seeker_id', $seekerId);
            }]);
            
            // Exclude jobs the seeker has already applied for
            // Note: whereDoesntHave conflicts with withCount, so we'll handle this in frontend
            // $query->whereDoesntHave('applications', function ($q) use ($seekerId) {
            //     $q->where('seeker_id', $seekerId);
            // });
        }

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('title_ar', 'like', "%{$search}%")
                    ->orWhere('description_ar', 'like', "%{$search}%");
            });
        }

        // Job type filter
        if ($jobType = $request->input('job_type')) {
            $query->where('job_type', $jobType);
        }

        // Experience level filter
        if ($experienceLevel = $request->input('experience_level')) {
            $query->where('experience_level', $experienceLevel);
        }

        // Location country filter
        if ($country = $request->input('location_country')) {
            $query->where('location_country', $country);
        }

        // City filter
        if ($city = $request->input('location_city')) {
            $query->where('location_city', 'like', "%{$city}%");
        }

        // Remote filter
        if ($request->has('is_remote')) {
            $query->where('is_remote', $request->boolean('is_remote'));
        }

        // Salary range filter
        if ($salaryMin = $request->input('salary_min')) {
            $query->where(function ($q) use ($salaryMin) {
                $q->where('salary_min', '>=', $salaryMin)
                    ->orWhere('salary_max', '>=', $salaryMin);
            });
        }

        if ($salaryMax = $request->input('salary_max')) {
            $query->where(function ($q) use ($salaryMax) {
                $q->where('salary_max', '<=', $salaryMax)
                    ->orWhere('salary_min', '<=', $salaryMax);
            });
        }

        $perPage = min($request->input('per_page', 15), 50);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'data' => JobPostingResource::collection($jobs),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * Get a single published job by ID or slug.
     * Increments view count.
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        $user = $request->user();

        $query = JobPosting::with(['employer'])
            ->active();

        if ($user) {
            $query->addSelect([
                'is_saved' => SavedJob::selectRaw('1')
                    ->whereColumn('saved_jobs.job_posting_id', 'job_postings.id')
                    ->where('saved_jobs.user_id', $user->id)
                    ->limit(1)
            ]);
        }

        // Check if identifier is numeric (ID) or string (slug)
        if (is_numeric($identifier)) {
            $job = $query->find($identifier);
        } else {
            $job = $query->where('slug', $identifier)->first();
        }

        if (!$job) {
            return response()->json(['message' => 'Job not found or no longer available'], 404);
        }

        // Increment view count
        $job->incrementViews();

        return response()->json([
            'data' => new JobPostingResource($job),
        ]);
    }

    /**
     * Get job statistics for public display.
     */
    public function stats(): JsonResponse
    {
        $activeJobs = JobPosting::active()->count();
        
        $byType = JobPosting::active()
            ->selectRaw('job_type, COUNT(*) as count')
            ->groupBy('job_type')
            ->pluck('count', 'job_type');

        $byCountry = JobPosting::active()
            ->selectRaw('location_country, COUNT(*) as count')
            ->groupBy('location_country')
            ->pluck('count', 'location_country');

        return response()->json([
            'data' => [
                'total_active_jobs' => $activeJobs,
                'by_type' => $byType,
                'by_country' => $byCountry,
            ],
        ]);
    }

    /**
     * Apply for a job
     */
    public function apply(Request $request, string $identifier): JsonResponse
    {
        $user = $request->user();
        
        // Find the job
        $query = JobPosting::active();
        if (is_numeric($identifier)) {
            $job = $query->find($identifier);
        } else {
            $job = $query->where('slug', $identifier)->first();
        }

        if (!$job) {
            return response()->json(['message' => 'Job not found or no longer available'], 404);
        }

        // Get seeker profile first (needed for duplicate check)
        $seeker = $user->seeker;
        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found. Please complete your seeker profile first.'], 404);
        }

        // Check if user has already applied
        $existingApplication = JobApplication::where('seeker_id', $seeker->id)
            ->where('job_posting_id', $job->id)
            ->first();

        if ($existingApplication) {
            return response()->json(['message' => 'You have already applied for this job'], 422);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'cover_letter' => 'nullable|string|max:5000',
            'current_salary' => 'nullable|string|max:255',
            'expected_salary' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check profile completion - require resume data for low completion profiles
        $profileCompletion = $seeker->profile_completion ?? 0;
        
        if ($profileCompletion < 50 && !$seeker->resume) {
            return response()->json([
                'message' => 'Resume is required. Your profile is ' . $profileCompletion . '% complete. Please complete your resume before applying.',
                'profile_completion' => $profileCompletion,
                'resume_required' => true,
            ], 422);
        }

        // Create the application
        $application = JobApplication::create([
            'seeker_id' => $seeker->id,
            'job_posting_id' => $job->id,
            'employer_id' => $job->employer_id,
            'status' => 'applied',
            'cover_letter' => $request->input('cover_letter'),
            'current_salary' => $request->input('current_salary'),
            'expected_salary' => $request->input('expected_salary'),
        ]);

        // Increment job application count
        $job->incrementApplications();

        // Store resume snapshot from seeker's current resume data
        if ($seeker->resume) {
            $application->update([
                'resume_snapshot' => $seeker->resume->toArray(),
            ]);
        }

        return response()->json([
            'message' => 'Application submitted successfully',
            'data' => [
                'application_id' => $application->id,
                'status' => $application->status,
                'applied_at' => $application->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Save or unsave a job for the authenticated user.
     */
    public function toggleSave(Request $request, string $identifier): JsonResponse
    {
        $user = $request->user();
        
        // Find the job
        $query = JobPosting::active();
        if (is_numeric($identifier)) {
            $job = $query->find($identifier);
        } else {
            $job = $query->where('slug', $identifier)->first();
        }

        if (!$job) {
            return response()->json(['message' => 'Job not found or no longer available'], 404);
        }

        // Check if already saved
        $savedJob = SavedJob::where('user_id', $user->id)
            ->where('job_posting_id', $job->id)
            ->first();

        if ($savedJob) {
            // Unsave the job
            $savedJob->delete();
            $isSaved = false;
        } else {
            // Save the job
            SavedJob::create([
                'user_id' => $user->id,
                'job_posting_id' => $job->id,
            ]);
            $isSaved = true;
        }

        return response()->json([
            'is_saved' => $isSaved,
            'message' => $isSaved ? 'Job saved successfully' : 'Job removed from saved jobs'
        ]);
    }

    /**
     * Get saved jobs for the authenticated user.
     */
    public function savedJobs(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $savedJobs = SavedJob::with(['jobPosting.employer'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $savedJobs->getCollection()->map(function ($savedJob) {
                $job = $savedJob->jobPosting;
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company' => $job->employer?->company_name,
                    'location' => $job->location_city ? "{$job->location_city}, {$job->location_country}" : $job->location_country,
                    'location_city' => $job->location_city,
                    'location_country' => $job->location_country,
                    'salary_min' => $job->salary_min,
                    'salary_max' => $job->salary_max,
                    'salary_currency' => $job->salary_currency,
                    'salary_period' => $job->salary_period,
                    'is_salary_visible' => $job->is_salary_visible,
                    'job_type' => $job->job_type,
                    'experience_level' => $job->experience_level,
                    'is_remote' => $job->is_remote,
                    'published_at' => $job->published_at?->toISOString(),
                    'application_deadline' => $job->application_deadline?->toDateString(),
                    'status' => $job->status,
                    'is_saved' => true,
                    'saved_date' => $savedJob->created_at->toDateString(),
                    'employer' => $job->employer,
                ];
            }),
            'meta' => [
                'current_page' => $savedJobs->currentPage(),
                'last_page' => $savedJobs->lastPage(),
                'per_page' => $savedJobs->perPage(),
                'total' => $savedJobs->total(),
            ],
        ]);
    }
}
