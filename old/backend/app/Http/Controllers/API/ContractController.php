<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\JobApplication;
use App\Models\Seeker;
use App\Models\VisaStatus;
use App\Models\VisaStep;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ContractController
 * 
 * Handles employment contract management for employers.
 */
class ContractController extends Controller
{
    public function __construct(
        protected EmployerService $employerService
    ) {}

    /**
     * List all contracts for the employer.
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $filters = $request->only(['status', 'job_id', 'seeker_id', 'search']);
        $perPage = $request->input('per_page', 15);

        $query = Contract::where('employer_id', $employer->id)
            ->with(['seeker', 'jobApplication']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['job_id'])) {
            $query->where('job_application_id', $filters['job_id']);
        }

        if (isset($filters['seeker_id'])) {
            $query->where('seeker_id', $filters['seeker_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%")
                  ->orWhereHas('seeker', function ($subQuery) use ($search) {
                      $subQuery->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => ContractResource::collection($contracts),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
        ]);
    }

    /**
     * Show a specific contract.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)
            ->with(['seeker', 'jobApplication', 'visaStatus'])
            ->findOrFail($id);

        return response()->json(new ContractResource($contract));
    }

    /**
     * Create a new contract (supports template-based or custom PDF upload).
     */
    public function store(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            // Required fields
            'seeker_id' => 'required|exists:seekers,id',
            'job_id' => 'nullable|exists:job_postings,id',
            'job_application_id' => 'nullable|exists:job_applications,id',
            'title' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            
            // Template or custom
            'template_id' => 'nullable|exists:contract_templates,id',
            'attachment' => 'nullable|file|mimes:pdf|max:10240',
            
            // Contract details
            'department' => 'nullable|string|max:255',
            'reporting_to' => 'nullable|string|max:255',
            'work_location' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'salary_currency' => 'required|string|size:3',
            'salary_period' => 'required|in:hourly,daily,weekly,monthly,yearly',
            'allowances' => 'nullable|array',
            'benefits' => 'nullable|array',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'contract_type' => 'required|in:permanent,fixed_term,probation,temporary',
            'probation_months' => 'nullable|integer|min:0|max:12',
            'notice_period_days' => 'nullable|integer|min:0|max:365',
            'working_hours' => 'required|string|max:255',
            'working_days_per_week' => 'required|integer|min:1|max:7',
            'terms' => 'nullable|string',
            'html_content' => 'nullable|string',
            'special_conditions' => 'nullable|string',
            'clauses' => 'nullable|array',
            'expires_at' => 'nullable|date|after:today',
            // Enhanced Branding (copied from template)
            'header_logo_path' => 'nullable|string|max:500',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'signatory_name' => 'nullable|string|max:255',
            'signatory_title' => 'nullable|string|max:255',
            'signatory_signature_path' => 'nullable|string|max:500',
            // Approval Workflow
            'approver_id' => 'nullable|exists:employer_users,id',
            'status' => 'nullable|in:draft,pending_internal_approval,sent',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        
        // Get seeker info
        $seeker = Seeker::with('user')->findOrFail($validated['seeker_id']);
        
        $validated['employer_id'] = $employer->id;
        $validated['created_by'] = $request->user()->id;
        
        // Set status - default to draft if not provided
        if (empty($validated['status'])) {
            $validated['status'] = 'draft';
        }
        
        // Validate approver if submitting for approval
        if ($validated['status'] === 'pending_internal_approval') {
            if (empty($validated['approver_id'])) {
                return response()->json(['message' => 'Approver is required when submitting for approval'], 422);
            }
            $approverExists = \App\Models\EmployerUser::where('id', $validated['approver_id'])
                ->where('employer_id', $employer->id)
                ->exists();
            if (!$approverExists) {
                return response()->json(['message' => 'Invalid approver selected'], 422);
            }
        }

        // Handle template-based contract
        if (!empty($validated['template_id'])) {
            $template = ContractTemplate::forEmployer($employer->id)->findOrFail($validated['template_id']);
            
            // Prepare placeholder data
            $placeholderData = [
                'candidate_name' => trim($seeker->first_name . ' ' . $seeker->last_name),
                'candidate_email' => $seeker->user?->email ?? '',
                'candidate_phone' => $seeker->user?->mobile ?? '',
                'job_title' => $validated['job_title'],
                'department' => $validated['department'] ?? '',
                'salary' => number_format($validated['salary'], 2),
                'salary_currency' => $validated['salary_currency'],
                'start_date' => \Carbon\Carbon::parse($validated['start_date'])->format('F j, Y'),
                'end_date' => isset($validated['end_date']) ? \Carbon\Carbon::parse($validated['end_date'])->format('F j, Y') : '',
                'work_location' => $validated['work_location'],
                'company_name' => $employer->company_name,
                'company_address' => $employer->address ?? '',
                'reporting_to' => $validated['reporting_to'] ?? '',
                'probation_period' => isset($validated['probation_months']) ? $validated['probation_months'] . ' months' : '',
                'notice_period' => isset($validated['notice_period_days']) ? $validated['notice_period_days'] . ' days' : '',
                'working_hours' => $validated['working_hours'],
                'current_date' => now()->format('F j, Y'),
            ];
            
            $validated['html_content'] = $template->fillPlaceholders($placeholderData);
            $validated['template_id'] = $template->id;
            $template->incrementUsage();
        }

        // Handle PDF attachment upload
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('contracts/attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        $contract = Contract::create($validated);

        return response()->json([
            'message' => 'Contract created successfully',
            'data' => new ContractResource($contract->load(['seeker', 'template'])),
        ], 201);
    }

    /**
     * Update a contract.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)->findOrFail($id);

        // Prevent updates to signed contracts
        if ($contract->status === 'signed') {
            return response()->json(['message' => 'Cannot update signed contract'], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'job_title' => 'sometimes|string|max:255',
            'department' => 'nullable|string|max:255',
            'reporting_to' => 'nullable|string|max:255',
            'work_location' => 'sometimes|string|max:255',
            'salary' => 'sometimes|numeric|min:0',
            'salary_currency' => 'sometimes|string|size:3',
            'salary_period' => 'sometimes|in:hourly,daily,weekly,monthly,yearly',
            'allowances' => 'nullable|array',
            'benefits' => 'nullable|array',
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'contract_type' => 'sometimes|in:permanent,fixed_term,probation,temporary',
            'probation_months' => 'nullable|integer|min:0|max:12',
            'notice_period_days' => 'nullable|integer|min:0|max:365',
            'working_hours' => 'sometimes|string|max:255',
            'working_days_per_week' => 'sometimes|integer|min:1|max:7',
            'terms' => 'sometimes|string',
            'special_conditions' => 'nullable|string',
            'clauses' => 'nullable|array',
            'template_used' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contract->update($validator->validated());

        return response()->json(new ContractResource($contract));
    }

    /**
     * Delete a contract.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)->findOrFail($id);

        // Prevent deletion of signed contracts
        if ($contract->status === 'signed') {
            return response()->json(['message' => 'Cannot delete signed contract'], 422);
        }

        $contract->delete();

        return response()->json(['message' => 'Contract deleted successfully']);
    }

    /**
     * Send contract to candidate.
     */
    public function send(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)->findOrFail($id);

        // Allow sending from draft or approved status
        if (!in_array($contract->status, ['draft', 'approved'])) {
            return response()->json(['message' => 'Only draft or approved contracts can be sent'], 422);
        }

        $contract->send();

        // Create activity item for seeker's action center
        $this->createContractActivity($contract);

        // TODO: Send email notification to candidate

        return response()->json(new ContractResource($contract));
    }

    /**
     * Submit contract for internal approval.
     */
    public function submitForApproval(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)->findOrFail($id);

        if ($contract->status !== 'draft') {
            return response()->json(['message' => 'Only draft contracts can be submitted for approval'], 422);
        }

        $validator = Validator::make($request->all(), [
            'approver_id' => 'required|exists:employer_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate approver belongs to this employer
        $approverExists = \App\Models\EmployerUser::where('id', $request->approver_id)
            ->where('employer_id', $employer->id)
            ->exists();

        if (!$approverExists) {
            return response()->json(['message' => 'Invalid approver selected'], 422);
        }

        $contract->submitForApproval($request->approver_id);

        // TODO: Send notification to approver

        return response()->json([
            'message' => 'Contract submitted for approval',
            'data' => new ContractResource($contract->fresh(['approver.user'])),
        ]);
    }

    /**
     * Approve a contract (internal approval).
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)
            ->with('approver')
            ->findOrFail($id);

        if ($contract->status !== 'pending_internal_approval') {
            return response()->json(['message' => 'Contract is not pending approval'], 422);
        }

        // Check if current user is the designated approver
        $employerUser = \App\Models\EmployerUser::where('employer_id', $employer->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$employerUser || $contract->approver_id !== $employerUser->id) {
            return response()->json(['message' => 'You are not authorized to approve this contract'], 403);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
            'send_immediately' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contract->approve($request->user()->id, $request->notes);

        // If send_immediately is true, also send the contract
        if ($request->boolean('send_immediately')) {
            $contract->send();
            // Create activity item for seeker's action center
            $this->createContractActivity($contract);
            // TODO: Send email notification to candidate
        }

        return response()->json([
            'message' => $request->boolean('send_immediately') 
                ? 'Contract approved and sent to candidate' 
                : 'Contract approved successfully',
            'data' => new ContractResource($contract->fresh()),
        ]);
    }

    /**
     * Reject a contract (internal approval).
     */
    public function rejectApproval(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)
            ->with('approver')
            ->findOrFail($id);

        if ($contract->status !== 'pending_internal_approval') {
            return response()->json(['message' => 'Contract is not pending approval'], 422);
        }

        // Check if current user is the designated approver
        $employerUser = \App\Models\EmployerUser::where('employer_id', $employer->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$employerUser || $contract->approver_id !== $employerUser->id) {
            return response()->json(['message' => 'You are not authorized to reject this contract'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contract->update([
            'status' => 'draft',
            'approval_notes' => $request->reason,
        ]);

        // TODO: Send notification to contract creator

        return response()->json([
            'message' => 'Contract approval rejected',
            'data' => new ContractResource($contract->fresh()),
        ]);
    }

    /**
     * Get contracts pending approval for current user.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        // Get current user's employer_user record
        $employerUser = \App\Models\EmployerUser::where('employer_id', $employer->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$employerUser) {
            return response()->json(['data' => []]);
        }

        $contracts = Contract::where('employer_id', $employer->id)
            ->where('status', 'pending_internal_approval')
            ->where('approver_id', $employerUser->id)
            ->with(['seeker', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => ContractResource::collection($contracts),
        ]);
    }

    /**
     * Create a revised version of a contract.
     */
    public function createRevision(Request $request, $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)->findOrFail($id);

        if (!in_array($contract->status, ['sent', 'viewed', 'rejected'])) {
            return response()->json(['message' => 'Can only revise sent, viewed, or rejected contracts'], 422);
        }

        $validator = Validator::make($request->all(), [
            'changes' => 'required|array',
            'negotiation_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $newContract = $contract->createRevision($validated['changes']);

        if (isset($validated['negotiation_notes'])) {
            $newContract->negotiation_notes = $validated['negotiation_notes'];
            $newContract->save();
        }

        return response()->json(new ContractResource($newContract), 201);
    }

    /**
     * Get contract statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $stats = [
            'total' => Contract::where('employer_id', $employer->id)->count(),
            'draft' => Contract::where('employer_id', $employer->id)->where('status', 'draft')->count(),
            'pending_internal_approval' => Contract::where('employer_id', $employer->id)->where('status', 'pending_internal_approval')->count(),
            'approved' => Contract::where('employer_id', $employer->id)->where('status', 'approved')->count(),
            'sent' => Contract::where('employer_id', $employer->id)->where('status', 'sent')->count(),
            'viewed' => Contract::where('employer_id', $employer->id)->where('status', 'viewed')->count(),
            'signed' => Contract::where('employer_id', $employer->id)->where('status', 'signed')->count(),
            'rejected' => Contract::where('employer_id', $employer->id)->where('status', 'rejected')->count(),
            'expired' => Contract::where('employer_id', $employer->id)->where('status', 'expired')->count(),
            'pending_signature' => Contract::where('employer_id', $employer->id)
                ->whereIn('status', ['sent', 'viewed'])
                ->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Get candidates available for contract creation.
     * Returns hired/interviewed candidates without active contracts.
     */
    public function getCandidatesForContract(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        // Get seekers who have been hired or interviewed but don't have active contracts
        $candidates = Seeker::with(['user', 'resume'])
            ->whereHas('applications', function ($q) use ($employer) {
                $q->whereHas('job', function ($jq) use ($employer) {
                    $jq->where('employer_id', $employer->id);
                })->whereIn('status', ['hired', 'interviewed', 'offer_extended']);
            })
            ->whereDoesntHave('contracts', function ($q) use ($employer) {
                $q->where('employer_id', $employer->id)
                  ->whereIn('status', ['draft', 'sent', 'viewed', 'signed']);
            })
            ->get()
            ->map(function ($seeker) use ($employer) {
                // Get the relevant application
                $application = $seeker->applications()
                    ->whereHas('job', fn($q) => $q->where('employer_id', $employer->id))
                    ->whereIn('status', ['hired', 'interviewed', 'offer_extended'])
                    ->with('job')
                    ->first();

                return [
                    'id' => $seeker->id,
                    'name' => trim($seeker->first_name . ' ' . $seeker->last_name),
                    'email' => $seeker->user?->email,
                    'phone' => $seeker->user?->mobile,
                    'job_title' => $application?->job?->title,
                    'job_id' => $application?->job_id,
                    'application_id' => $application?->id,
                    'application_status' => $application?->status,
                    'profile_image_url' => $seeker->profile_image_path 
                        ? Storage::disk('public')->url($seeker->profile_image_path) 
                        : null,
                ];
            });

        return response()->json(['data' => $candidates]);
    }

    /**
     * Initiate visa process for a signed contract.
     */
    public function initiateVisaProcess(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)->findOrFail($id);

        if ($contract->status !== 'signed') {
            return response()->json(['message' => 'Can only initiate visa process for signed contracts'], 422);
        }

        // Check if visa process already exists
        if ($contract->visaStatus) {
            return response()->json(['message' => 'Visa process already initiated'], 422);
        }

        $validator = Validator::make($request->all(), [
            'visa_type' => 'required|string|max:100',
            'destination_country' => 'required|string|size:3',
            'origin_country' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Create visa status
        $visaStatus = VisaStatus::create([
            'contract_id' => $contract->id,
            'employer_id' => $employer->id,
            'seeker_id' => $contract->seeker_id,
            'visa_type' => $validated['visa_type'],
            'destination_country' => $validated['destination_country'],
            'origin_country' => $validated['origin_country'] ?? null,
            'current_step' => 'documents_pending',
            'last_updated_by' => $request->user()->id,
        ]);

        // Create all visa steps
        VisaStep::createAllForVisaStatus($visaStatus->id);

        // Mark first step as in progress
        $firstStep = $visaStatus->steps()->where('step_order', 1)->first();
        if ($firstStep) {
            $firstStep->start();
        }

        return response()->json([
            'message' => 'Visa process initiated successfully',
            'data' => $visaStatus->load('steps'),
        ], 201);
    }

    /**
     * Upload attachment to existing contract.
     */
    public function uploadAttachment(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)->findOrFail($id);

        if (!in_array($contract->status, ['draft'])) {
            return response()->json(['message' => 'Can only upload attachments to draft contracts'], 422);
        }

        $validator = Validator::make($request->all(), [
            'attachment' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Delete old attachment if exists
        if ($contract->attachment_path) {
            Storage::disk('public')->delete($contract->attachment_path);
        }

        $path = $request->file('attachment')->store('contracts/attachments', 'public');
        $contract->update(['attachment_path' => $path]);

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'data' => new ContractResource($contract->fresh()),
        ]);
    }

    /**
     * Preview contract content (for template-based contracts).
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $contract = Contract::where('employer_id', $employer->id)
            ->with(['seeker.user', 'template'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'html_content' => $contract->html_content,
                'attachment_url' => $contract->attachment_path 
                    ? Storage::disk('public')->url($contract->attachment_path) 
                    : null,
                'template' => $contract->template ? [
                    'name' => $contract->template->name,
                    'header_image_url' => $contract->template->header_image_path 
                        ? Storage::disk('public')->url($contract->template->header_image_path) 
                        : null,
                    'footer_text' => $contract->template->footer_text,
                ] : null,
            ],
        ]);
    }

    /**
     * Create activity item for contract notifications.
     */
    private function createContractActivity(Contract $contract): void
    {
        try {
            // Update the related job application status to contract_sent
            if ($contract->jobApplication) {
                $contract->jobApplication->update([
                    'status' => 'contract_sent',
                    'status_changed_at' => now(),
                ]);
            }

            Log::info('Contract activity created', [
                'contract_id' => $contract->id,
                'seeker_id' => $contract->seeker_id,
                'employer_id' => $contract->employer_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create contract activity', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
