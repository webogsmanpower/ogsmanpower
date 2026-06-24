<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobApplicationResource;
use App\Models\JobApplication;
use App\Services\ApplicationService;
use App\Services\CandidateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * SeekerApplicationController
 * 
 * Handles job application management for seekers.
 */
class SeekerApplicationController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService,
        protected CandidateService $candidateService
    ) {}

    /**
     * List all applications for the seeker.
     */
    public function index(Request $request): JsonResponse
    {
        $seeker = $this->candidateService->getSeekerForUser($request->user());

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $filters = $request->only(['status', 'search', 'is_favorite']);
        $perPage = $request->input('per_page', 15);

        $applications = $this->applicationService->getApplicationsForSeeker($seeker, $filters, $perPage);

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
        $seeker = $this->candidateService->getSeekerForUser($request->user());

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationByIdForSeeker($seeker, $id);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        return response()->json([
            'data' => new JobApplicationResource($application->load([
                'jobPosting.employer',
                'employer',
                'interviews',
                'contract',
            ])),
        ]);
    }

    /**
     * Withdraw an application.
     */
    public function withdraw(Request $request, int $id): JsonResponse
    {
        $seeker = $this->candidateService->getSeekerForUser($request->user());

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationByIdForSeeker($seeker, $id);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $application->updateStatus('withdrawn', $request->user(), $request->input('reason'));

        if (!$result) {
            return response()->json([
                'message' => 'Cannot withdraw application at this stage',
            ], 422);
        }

        return response()->json([
            'message' => 'Application withdrawn successfully',
            'data' => new JobApplicationResource($application->fresh()),
        ]);
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $seeker = $this->candidateService->getSeekerForUser($request->user());

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $application = $this->applicationService->getApplicationByIdForSeeker($seeker, $id);

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
     * Get application activity feed.
     */
    public function activity(Request $request): JsonResponse
    {
        $seeker = $this->candidateService->getSeekerForUser($request->user());

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $limit = min($request->input('limit', 50), 100); // Max 100 items
        $activities = $this->applicationService->getApplicationActivity($seeker, $limit);

        return response()->json([
            'data' => $activities,
        ]);
    }

    /**
     * Get seeker's application statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $seeker = $this->candidateService->getSeekerForUser($request->user());

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $stats = $this->applicationService->getSeekerApplicationStats($seeker);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
