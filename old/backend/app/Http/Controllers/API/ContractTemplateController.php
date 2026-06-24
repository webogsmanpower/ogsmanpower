<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * ContractTemplateController
 * 
 * Handles contract template CRUD operations for employers.
 */
class ContractTemplateController extends Controller
{
    public function __construct(
        protected EmployerService $employerService
    ) {}

    /**
     * List all templates for the employer.
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $templates = ContractTemplate::forEmployer($employer->id)
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $templates,
            'placeholders' => ContractTemplate::DEFAULT_PLACEHOLDERS,
        ]);
    }

    /**
     * Get a specific template.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $template = ContractTemplate::forEmployer($employer->id)->findOrFail($id);

        return response()->json([
            'data' => $template->load('defaultApprover.user'),
            'placeholders' => $template->getAllPlaceholders(),
        ]);
    }

    /**
     * Create a new template.
     */
    public function store(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'placeholders' => 'nullable|array',
            'placeholders.*.key' => 'required_with:placeholders|string|max:50',
            'placeholders.*.label' => 'required_with:placeholders|string|max:100',
            'header_image' => 'nullable|image|max:2048',
            'footer_text' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            // Enhanced Branding
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'signatory_name' => 'nullable|string|max:255',
            'signatory_title' => 'nullable|string|max:255',
            'signatory_signature' => 'nullable|image|max:1024',
            // Approval Workflow
            'default_approver_id' => 'nullable|exists:employer_users,id',
            'requires_approval' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['employer_id'] = $employer->id;
        $data['created_by'] = $request->user()->id;

        // Handle header image upload
        if ($request->hasFile('header_image')) {
            $path = $request->file('header_image')->store('contract-templates/headers', 'public');
            $data['header_image_path'] = $path;
        }

        // Handle signatory signature upload
        if ($request->hasFile('signatory_signature')) {
            $path = $request->file('signatory_signature')->store('contract-templates/signatures', 'public');
            $data['signatory_signature_path'] = $path;
        }

        // Validate approver belongs to this employer
        if (!empty($data['default_approver_id'])) {
            $approverExists = \App\Models\EmployerUser::where('id', $data['default_approver_id'])
                ->where('employer_id', $employer->id)
                ->exists();
            if (!$approverExists) {
                return response()->json(['message' => 'Invalid approver selected'], 422);
            }
        }

        $template = ContractTemplate::create($data);
        
        \Log::info('ContractTemplate created', [
            'template_id' => $template->id,
            'employer_id' => $employer->id,
            'created_data' => $data,
            'content' => $data['content'] ?? 'no content'
        ]);

        // Set as default if requested
        if ($request->boolean('is_default')) {
            $template->setAsDefault();
        }

        return response()->json([
            'message' => 'Template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Debug endpoint to check template content in database
     */
    public function debug(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $template = ContractTemplate::forEmployer($employer->id)->findOrFail($id);
        
        return response()->json([
            'template_id' => $id,
            'name' => $template->name,
            'content_length' => strlen($template->content ?? ''),
            'content_preview' => substr($template->content ?? '', 0, 200) . '...',
            'updated_at' => $template->updated_at,
            'created_at' => $template->created_at,
            'full_content' => $template->content
        ]);
    }

    /**
     * Update a template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $template = ContractTemplate::forEmployer($employer->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'content' => 'sometimes|string|min:10',
            'placeholders' => 'nullable|array',
            'placeholders.*.key' => 'required_with:placeholders|string|max:50',
            'placeholders.*.label' => 'required_with:placeholders|string|max:100',
            'header_image' => 'nullable|image|max:2048',
            'footer_text' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            // Enhanced Branding
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'signatory_name' => 'nullable|string|max:255',
            'signatory_title' => 'nullable|string|max:255',
            'signatory_signature' => 'nullable|image|max:1024',
            // Approval Workflow
            'default_approver_id' => 'nullable|exists:employer_users,id',
            'requires_approval' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Handle header image upload
        if ($request->hasFile('header_image')) {
            // Delete old image
            if ($template->header_image_path) {
                Storage::disk('public')->delete($template->header_image_path);
            }
            $path = $request->file('header_image')->store('contract-templates/headers', 'public');
            $data['header_image_path'] = $path;
        }

        // Handle signatory signature upload
        if ($request->hasFile('signatory_signature')) {
            // Delete old signature
            if ($template->signatory_signature_path) {
                Storage::disk('public')->delete($template->signatory_signature_path);
            }
            $path = $request->file('signatory_signature')->store('contract-templates/signatures', 'public');
            $data['signatory_signature_path'] = $path;
        }

        // Validate approver belongs to this employer
        if (!empty($data['default_approver_id'])) {
            $approverExists = \App\Models\EmployerUser::where('id', $data['default_approver_id'])
                ->where('employer_id', $employer->id)
                ->exists();
            if (!$approverExists) {
                return response()->json(['message' => 'Invalid approver selected'], 422);
            }
        }

        $template->update($data);

        // Set as default if requested
        if ($request->boolean('is_default')) {
            $template->setAsDefault();
        }

        return response()->json([
            'message' => 'Template updated successfully',
            'data' => $template->fresh(),
        ]);
    }

    /**
     * Delete a template (soft delete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $template = ContractTemplate::forEmployer($employer->id)->findOrFail($id);

        // Check if template is in use
        if ($template->usage_count > 0) {
            // Soft delete - keep for historical reference
            $template->update(['is_active' => false]);
            $template->delete();
        } else {
            // Hard delete if never used
            if ($template->header_image_path) {
                Storage::disk('public')->delete($template->header_image_path);
            }
            $template->forceDelete();
        }

        return response()->json(['message' => 'Template deleted successfully']);
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $template = ContractTemplate::forEmployer($employer->id)->findOrFail($id);

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->is_default = false;
        $newTemplate->usage_count = 0;
        $newTemplate->created_by = $request->user()->id;
        $newTemplate->save();

        return response()->json([
            'message' => 'Template duplicated successfully',
            'data' => $newTemplate,
        ], 201);
    }

    /**
     * Preview template with sample data.
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $template = ContractTemplate::forEmployer($employer->id)->findOrFail($id);

        // Sample data for preview
        $sampleData = [
            'candidate_name' => 'John Doe',
            'candidate_email' => 'john.doe@example.com',
            'candidate_phone' => '+1 234 567 8900',
            'candidate_address' => '123 Main Street, City, Country',
            'job_title' => 'Software Engineer',
            'department' => 'Engineering',
            'salary' => '5,000',
            'salary_currency' => 'USD',
            'start_date' => now()->addDays(30)->format('F j, Y'),
            'end_date' => now()->addYear()->format('F j, Y'),
            'work_location' => 'Dubai, UAE',
            'company_name' => $employer->company_name,
            'company_country' => 'UAE',
            'company_address' => $template->company_address ?? $employer->address ?? '123 Business Street',
            'company_phone' => $template->company_phone ?? $employer->phone ?? '+971 4 123 4567',
            'company_email' => $template->company_email ?? $employer->email ?? 'info@company.com',
            'reporting_to' => 'Jane Smith',
            'probation_months' => '3',
            'probation_period' => '3 months',
            'notice_period_days' => '30',
            'notice_period' => '30 days',
            'working_hours' => '9:00 AM - 6:00 PM',
            'current_date' => now()->format('F j, Y'),
            'signatory_name' => $template->signatory_name ?? 'Authorized Signatory',
            'signatory_title' => $template->signatory_title ?? 'HR Director',
        ];

        $previewContent = $template->fillPlaceholders($sampleData);

        return response()->json([
            'data' => [
                'content' => $previewContent,
                'header_image_url' => $template->header_image_path 
                    ? Storage::disk('public')->url($template->header_image_path) 
                    : null,
                'footer_text' => $template->footer_text,
                'company_address' => $template->company_address,
                'company_phone' => $template->company_phone,
                'company_email' => $template->company_email,
                'signatory_name' => $template->signatory_name,
                'signatory_title' => $template->signatory_title,
                'signatory_signature_url' => $template->signatory_signature_path
                    ? Storage::disk('public')->url($template->signatory_signature_path)
                    : null,
            ],
        ]);
    }

    /**
     * Get available placeholders.
     */
    public function placeholders(): JsonResponse
    {
        return response()->json([
            'data' => ContractTemplate::DEFAULT_PLACEHOLDERS,
        ]);
    }
}
