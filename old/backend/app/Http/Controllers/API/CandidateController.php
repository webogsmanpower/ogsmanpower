<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeekerResource;
use App\Services\CandidateService;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CandidateController
 * 
 * Handles candidate browsing, searching, and manual creation by employers.
 * Provides access to all seekers with complete profiles for recruitment.
 */
class CandidateController extends Controller
{
    public function __construct(
        protected CandidateService $candidateService,
        protected EmployerService $employerService
    ) {}

    /**
     * List all candidates (seekers with complete profiles).
     * 
     * Filters:
     * - search: Search in name, skills, location
     * - skills: Filter by skills (comma-separated)
     * - location: Filter by location
     * - experience_min: Minimum experience years
     * - experience_max: Maximum experience years
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $candidates = $this->candidateService->getAllCandidates(
            $request->only(['search', 'skills', 'location', 'experience_min', 'experience_max']),
            $request->input('per_page', 15)
        );

        return response()->json([
            'data' => SeekerResource::collection($candidates),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ],
        ]);
    }

    /**
     * Search candidates with advanced filters.
     */
    public function search(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $candidates = $this->candidateService->searchCandidates(
            $request->all(),
            $request->input('per_page', 15)
        );

        return response()->json([
            'data' => SeekerResource::collection($candidates),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ],
        ]);
    }

    /**
     * Get candidates eligible for contract creation.
     * Returns seekers who have applied to this employer's jobs or been shortlisted.
     */
    public function forContract(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $candidates = $this->candidateService->getCandidatesForContract($employer);

        return response()->json([
            'data' => $candidates,
        ]);
    }

    /**
     * Create a new candidate manually.
     * Replicates the EditResume form structure.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Basic Information
            'basic_information' => 'required|array',
            'basic_information.first_name' => 'required|string|max:100',
            'basic_information.last_name' => 'required|string|max:100',
            'basic_information.email' => 'required|email|max:255',
            'basic_information.phone' => 'required|string|max:20',
            'basic_information.date_of_birth' => 'nullable|date',
            'basic_information.father_name' => 'nullable|string|max:100',
            'basic_information.mother_name' => 'nullable|string|max:100',
            'basic_information.marital_status' => 'nullable|in:single,married,divorced,widowed',
            'basic_information.nationality' => 'nullable|string|max:100',
            'basic_information.whatsapp_number' => 'nullable|string|max:20',
            'basic_information.emergency_contact_name' => 'nullable|string|max:100',
            'basic_information.emergency_contact_phone' => 'nullable|string|max:20',
            'basic_information.address' => 'nullable|string|max:500',
            'basic_information.city' => 'nullable|string|max:100',
            'basic_information.state_province' => 'nullable|string|max:100',
            'basic_information.country' => 'nullable|string|max:3',
            'basic_information.zip_code' => 'nullable|string|max:20',
            
            // Documents
            'documents' => 'nullable|array',
            'documents.passport_number' => 'nullable|string|max:50',
            'documents.passport_issue_date' => 'nullable|date',
            'documents.passport_expiry_date' => 'nullable|date',
            'documents.passport_issue_place' => 'nullable|string|max:100',
            'documents.cnic_number' => 'nullable|string|max:50',
            
            // Professional Summary
            'professional_summary' => 'nullable|array',
            'professional_summary.career_objective' => 'nullable|string|max:2000',
            'professional_summary.key_strengths' => 'nullable|array',
            'professional_summary.industry_experience' => 'nullable|string|max:500',
            
            // Work Experience
            'work_experience' => 'nullable|array',
            'work_experience.*.role_title' => 'required|string|max:100',
            'work_experience.*.company_name' => 'required|string|max:200',
            'work_experience.*.location' => 'nullable|string|max:100',
            'work_experience.*.start_date' => 'nullable|date',
            'work_experience.*.end_date' => 'nullable|date',
            'work_experience.*.is_current_role' => 'nullable|boolean',
            'work_experience.*.job_description' => 'nullable|string|max:2000',
            'work_experience.*.key_achievements' => 'nullable|string|max:1000',
            
            // Education
            'education' => 'nullable|array',
            'education.*.institution_name' => 'required|string|max:200',
            'education.*.degree_title' => 'required|string|max:200',
            'education.*.graduation_year' => 'nullable|date',
            
            // Skills
            'skills' => 'nullable|array',
            'skills.*.skill' => 'required|string|max:100',
            'skills.*.proficiency' => 'nullable|in:Beginner,Intermediate,Advanced,Expert',
            'skills.*.category' => 'nullable|string|max:50',
            
            // Languages
            'languages' => 'nullable|array',
            'languages.*.language_name' => 'required|string|max:50',
            'languages.*.proficiency_level' => 'nullable|in:native,fluent,intermediate',
            
            // Certifications
            'certifications' => 'nullable|array',
            'certifications.*.certification_name' => 'required|string|max:200',
            'certifications.*.issuer' => 'required|string|max:200',
            'certifications.*.issue_date' => 'nullable|date',
            'certifications.*.expiry_date' => 'nullable|date',
            'certifications.*.does_not_expire' => 'nullable|boolean',
            'certifications.*.credential_id' => 'nullable|string|max:100',
            'certifications.*.credential_url' => 'nullable|url|max:500',
            
            // References
            'references' => 'nullable|array',
            'references.*.name' => 'required|string|max:100',
            'references.*.job_title' => 'nullable|string|max:100',
            'references.*.company_name' => 'nullable|string|max:200',
            'references.*.email' => 'nullable|email|max:255',
            'references.*.phone' => 'nullable|string|max:20',
            'references.*.relationship' => 'nullable|string|max:50',
            
            // Job Preferences
            'job_preferences' => 'nullable|array',
            'job_preferences.preferred_locations' => 'nullable|string|max:500',
            'job_preferences.job_types' => 'nullable|string|max:200',
            'job_preferences.salary_expectations' => 'nullable|string|max:100',
            
            // Availability
            'availability' => 'nullable|array',
            'availability.availability_date' => 'nullable|date',
            'availability.notice_period' => 'nullable|in:immediate,30days,60days',
            
            // Job to apply (optional)
            'job_posting_id' => 'nullable|exists:job_postings,id',
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

        $result = $this->candidateService->createCandidate(
            $employer,
            $request->user(),
            $validator->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'message' => 'Candidate created successfully',
            'data' => [
                'seeker' => new SeekerResource($result['seeker']->load('resume')),
                'application' => $result['application'] ?? null,
            ],
        ], 201);
    }

    /**
     * Update an existing candidate.
     */
    public function update(Request $request, int $seekerId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'basic_information' => 'sometimes|array',
            'documents' => 'nullable|array',
            'professional_summary' => 'nullable|array',
            'work_experience' => 'nullable|array',
            'education' => 'nullable|array',
            'skills' => 'nullable|array',
            'languages' => 'nullable|array',
            'certifications' => 'nullable|array',
            'references' => 'nullable|array',
            'job_preferences' => 'nullable|array',
            'availability' => 'nullable|array',
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

        $result = $this->candidateService->updateCandidate(
            $employer,
            $seekerId,
            $validator->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code'] ?? 422);
        }

        return response()->json([
            'message' => 'Candidate updated successfully',
            'data' => new SeekerResource($result['seeker']->load('resume')),
        ]);
    }

    /**
     * Get candidate details.
     * Records profile view for analytics.
     */
    public function show(Request $request, int $seekerId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $seeker = $this->candidateService->getCandidateForEmployer($employer, $seekerId);

        if (!$seeker) {
            return response()->json(['message' => 'Candidate not found'], 404);
        }

        // Record profile view (don't count own views)
        if ($request->user()->id !== $seeker->user_id) {
            $seeker->increment('profile_views');
        }

        return response()->json([
            'data' => new SeekerResource($seeker->load(['resume', 'user'])),
        ]);
    }
}
