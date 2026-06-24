<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use App\Services\JobPostingService;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * JobPostingController
 * 
 * Handles job posting CRUD operations.
 */
class JobPostingController extends Controller
{
    public function __construct(
        protected JobPostingService $jobPostingService,
        protected EmployerService $employerService
    ) {}

    /**
     * List all jobs for the employer.
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $filters = $request->only(['status', 'job_type', 'search']);
        $perPage = $request->input('per_page', 15);

        $jobs = $this->jobPostingService->getJobsForEmployer($employer, $filters, $perPage);
        
        // Load applications for pipeline counts and createdBy for posted by name
        $jobs->load(['applications', 'createdBy']);

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
     * Create a new job posting.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'description' => 'required|string|max:10000',
            'description_ar' => 'nullable|string|max:10000',
            'requirements' => 'nullable|array',
            'responsibilities' => 'nullable|array',
            'job_type' => 'required|in:full_time,part_time,contract,temporary,internship',
            'experience_level' => 'required|in:entry,junior,mid,senior,executive',
            'experience_years_min' => 'nullable|integer|min:0|max:50',
            'experience_years_max' => 'nullable|integer|min:0|max:50|gte:experience_years_min',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'salary_currency' => 'nullable|string|max:3',
            'salary_period' => 'nullable|in:hourly,daily,weekly,monthly,yearly',
            'is_salary_visible' => 'nullable|boolean',
            'location_country' => 'required|string|max:3',
            'location_city' => 'nullable|string|max:100',
            'location_address' => 'nullable|string|max:255',
            'is_remote' => 'nullable|boolean',
            'skills_required' => 'nullable|array',
            'skills_preferred' => 'nullable|array',
            'benefits' => 'nullable|array',
            'languages_required' => 'nullable|array',
            'vacancies' => 'nullable|integer|min:1',
            'application_deadline' => 'nullable|date|after:today',
            'status' => 'nullable|in:draft,published',
            // Enhanced fields
            'contract_duration' => 'nullable|string|max:50',
            'visa_type' => 'nullable|string|max:50',
            'gender_preference' => 'nullable|in:male,female,any',
            'age_min' => 'nullable|integer|min:18|max:65',
            'age_max' => 'nullable|integer|min:18|max:65|gte:age_min',
            'nationality_preference' => 'nullable|string|max:100',
            'functional_area' => 'nullable|string|max:100',
            'housing_allowance' => 'nullable|boolean',
            'transportation_allowance' => 'nullable|boolean',
            'food_allowance' => 'nullable|boolean',
            'overtime_allowance' => 'nullable|boolean',
            'medical_insurance' => 'nullable|boolean',
            'annual_ticket' => 'nullable|boolean',
            'working_hours' => 'nullable|string|max:50',
            'working_days' => 'nullable|string|max:50',
            // Screening & Assessments
            'screening_questions' => 'nullable|array',
            'screening_questions.*.question' => 'required|string|max:500',
            'screening_questions.*.type' => 'required|in:text,multiple_choice,yes_no',
            'screening_questions.*.required' => 'boolean',
            'screening_questions.*.options' => 'required_if:type,multiple_choice|array',
            'assessment_ids' => 'nullable|array',
            'assessment_ids.*' => 'integer|exists:assessments,id',
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

        $job = $this->jobPostingService->createJob($employer, $request->user(), $validator->validated());

        return response()->json([
            'message' => 'Job posting created successfully',
            'data' => new JobPostingResource($job),
        ], 201);
    }

    /**
     * Get a specific job posting.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $job = $this->jobPostingService->getJobById($employer, $id);

        if (!$job) {
            return response()->json(['message' => 'Job posting not found'], 404);
        }

        return response()->json([
            'data' => new JobPostingResource($job->load(['applications.seeker'])),
        ]);
    }

    /**
     * Update a job posting.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'description' => 'sometimes|string|max:10000',
            'description_ar' => 'nullable|string|max:10000',
            'requirements' => 'nullable|array',
            'responsibilities' => 'nullable|array',
            'job_type' => 'sometimes|in:full_time,part_time,contract,temporary,internship',
            'experience_level' => 'sometimes|in:entry,junior,mid,senior,executive',
            'experience_years_min' => 'nullable|integer|min:0|max:50',
            'experience_years_max' => 'nullable|integer|min:0|max:50',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:3',
            'salary_period' => 'nullable|in:hourly,daily,weekly,monthly,yearly',
            'is_salary_visible' => 'nullable|boolean',
            'location_country' => 'sometimes|string|max:3',
            'location_city' => 'nullable|string|max:100',
            'location_address' => 'nullable|string|max:255',
            'is_remote' => 'nullable|boolean',
            'skills_required' => 'nullable|array',
            'skills_preferred' => 'nullable|array',
            'benefits' => 'nullable|array',
            'languages_required' => 'nullable|array',
            'vacancies' => 'nullable|integer|min:1',
            'application_deadline' => 'nullable|date',
            // Enhanced fields
            'contract_duration' => 'nullable|string|max:50',
            'visa_type' => 'nullable|string|max:50',
            'gender_preference' => 'nullable|in:male,female,any',
            'age_min' => 'nullable|integer|min:18|max:65',
            'age_max' => 'nullable|integer|min:18|max:65',
            'nationality_preference' => 'nullable|string|max:100',
            'functional_area' => 'nullable|string|max:100',
            'housing_allowance' => 'nullable|boolean',
            'transportation_allowance' => 'nullable|boolean',
            'food_allowance' => 'nullable|boolean',
            'overtime_allowance' => 'nullable|boolean',
            'medical_insurance' => 'nullable|boolean',
            'annual_ticket' => 'nullable|boolean',
            'working_hours' => 'nullable|string|max:50',
            'working_days' => 'nullable|string|max:50',
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

        $job = $this->jobPostingService->getJobById($employer, $id);

        if (!$job) {
            return response()->json(['message' => 'Job posting not found'], 404);
        }

        $job = $this->jobPostingService->updateJob($job, $validator->validated());

        return response()->json([
            'message' => 'Job posting updated successfully',
            'data' => new JobPostingResource($job),
        ]);
    }

    /**
     * Delete a job posting.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $job = $this->jobPostingService->getJobById($employer, $id);

        if (!$job) {
            return response()->json(['message' => 'Job posting not found'], 404);
        }

        $this->jobPostingService->deleteJob($job);

        return response()->json([
            'message' => 'Job posting deleted successfully',
        ]);
    }

    /**
     * Update job status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,published,paused,closed,filled',
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

        $job = $this->jobPostingService->getJobById($employer, $id);

        if (!$job) {
            return response()->json(['message' => 'Job posting not found'], 404);
        }

        $job = $this->jobPostingService->updateJobStatus($job, $request->input('status'));

        return response()->json([
            'message' => 'Job status updated successfully',
            'data' => new JobPostingResource($job),
        ]);
    }
}
