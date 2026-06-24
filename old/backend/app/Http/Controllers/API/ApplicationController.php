<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobApplicationResource;
use App\Models\JobApplication;
use App\Services\ApplicationService;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ApplicationController
 * 
 * Handles job application management for employers.
 */
class ApplicationController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService,
        protected EmployerService $employerService
    ) {}

    /**
     * List all applications for the employer.
     */
    public function index(Request $request): JsonResponse
    {
        Log::debug('ApplicationController::index called', [
            'user_id' => $request->user()->id,
            'filters' => $request->only(['status', 'job_id', 'search', 'is_favorite', 'source']),
        ]);
        
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            Log::warning('ApplicationController::index - Employer not found', [
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        Log::debug('ApplicationController::index - Employer found', [
            'employer_id' => $employer->id,
        ]);

        $filters = $request->only(['status', 'job_id', 'search', 'is_favorite', 'source']);
        $perPage = $request->input('per_page', 15);

        $applications = $this->applicationService->getApplicationsForEmployer($employer, $filters, $perPage);

        Log::debug('ApplicationController::index - Applications fetched', [
            'total' => $applications->total(),
            'count' => $applications->count(),
        ]);

        return response()->json([
            'data' => JobApplicationResource::collection($applications),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    /**
     * Get applications for a specific job.
     */
    public function forJob(Request $request, int $jobId): JsonResponse
    {
        Log::debug('ApplicationController::forJob called', [
            'job_id' => $jobId,
            'user_id' => $request->user()->id,
        ]);
        
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            Log::warning('ApplicationController::forJob - Employer not found', [
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        Log::debug('ApplicationController::forJob - Employer found', [
            'employer_id' => $employer->id,
        ]);

        $filters = $request->only(['status', 'search', 'is_favorite']);
        $filters['job_id'] = $jobId;
        $perPage = $request->input('per_page', 15);

        $applications = $this->applicationService->getApplicationsForEmployer($employer, $filters, $perPage);

        Log::debug('ApplicationController::forJob - Applications fetched', [
            'total' => $applications->total(),
            'count' => $applications->count(),
        ]);

        return response()->json([
            'data' => JobApplicationResource::collection($applications),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    /**
     * Get a specific application.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationById($employer, $id);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Mark as viewed
        $application->markAsViewed();

        return response()->json([
            'data' => new JobApplicationResource($application->load([
                'seeker.user',
                'seeker.resume',
                'jobPosting',
                'interviews',
                'contract',
                'documentVerifications',
            ])),
        ]);
    }

    /**
     * Update application status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:reviewed,shortlisted,interview_scheduled,interviewed,contract_sent,hired,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255',
            'rejection_feedback' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationById($employer, $id);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $result = $this->applicationService->updateStatus(
            $application,
            $request->input('status'),
            $request->user(),
            $request->input('rejection_reason'),
            $request->input('rejection_feedback'),
            $request->input('notes')
        );

        if (!$result) {
            return response()->json([
                'message' => 'Invalid status transition',
            ], 422);
        }

        return response()->json([
            'message' => 'Application status updated successfully',
            'data' => new JobApplicationResource($application->fresh()),
        ]);
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationById($employer, $id);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $application->toggleFavorite();

        return response()->json([
            'message' => $application->is_favorite ? 'Added to favorites' : 'Removed from favorites',
            'data' => [
                'is_favorite' => $application->is_favorite,
            ],
        ]);
    }

    /**
     * Add/update notes for an application.
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationById($employer, $id);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $application->update(['notes' => $request->input('notes')]);

        return response()->json([
            'message' => 'Notes updated successfully',
            'data' => new JobApplicationResource($application->fresh()),
        ]);
    }

    /**
     * Rate an application.
     */
    public function rate(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationById($employer, $id);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $application->update(['rating' => $request->input('rating')]);

        return response()->json([
            'message' => 'Rating updated successfully',
            'data' => new JobApplicationResource($application->fresh()),
        ]);
    }

    /**
     * Get application pipeline statistics.
     */
    public function pipelineStats(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $jobId = $request->input('job_id');
        $stats = $this->applicationService->getPipelineStats($employer, $jobId);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
