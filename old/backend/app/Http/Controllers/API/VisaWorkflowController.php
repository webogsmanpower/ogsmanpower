<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisaStepResource;
use App\Models\VisaStatus;
use App\Models\VisaStep;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * VisaWorkflowController
 *
 * Handles workflow management for existing VisaStep records.
 * Allows employers to reorder, rename, and delete steps for specific seekers.
 */
class VisaWorkflowController extends Controller
{
    public function __construct(
        protected EmployerService $employerService
    ) {}

    /**
     * Get all workflow steps for a visa status.
     */
    public function index(Request $request, int $visaStatusId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $steps = VisaStep::where('visa_status_id', $visaStatusId)
            ->orderBy('step_order')
            ->get();

        return response()->json([
            'data' => VisaStepResource::collection($steps),
        ]);
    }

    /**
     * Update a workflow step (rename, reorder, or delete).
     */
    public function update(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $step = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->first();

        if (!$step) {
            return response()->json(['message' => 'Step not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'nullable|string|max:255',
            'step_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:pending,in_progress,completed,skipped,blocked',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update step
        if ($request->has('label')) {
            $step->label = $request->input('label');
        }
        if ($request->has('step_order')) {
            $step->step_order = $request->input('step_order');
        }
        if ($request->has('status')) {
            $step->status = $request->input('status');
        }
        if ($request->has('notes')) {
            $step->notes = $request->input('notes');
        }

        $step->save();

        return response()->json([
            'data' => new VisaStepResource($step),
        ]);
    }

    /**
     * Delete a workflow step (mark as skipped).
     */
    public function destroy(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $step = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->first();

        if (!$step) {
            return response()->json(['message' => 'Step not found'], 404);
        }

        // Mark as skipped instead of deleting
        $step->status = 'skipped';
        $step->notes = $step->notes . "\n\nSkipped by employer on " . now()->toDateTimeString();
        $step->save();

        return response()->json([
            'message' => 'Step skipped successfully',
        ]);
    }

    /**
     * Reorder workflow steps.
     */
    public function reorder(Request $request, int $visaStatusId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'steps' => 'required|array',
            'steps.*.id' => 'required|integer',
            'steps.*.step_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->input('steps') as $stepData) {
                VisaStep::where('id', $stepData['id'])
                    ->where('visa_status_id', $visaStatusId)
                    ->update(['step_order' => $stepData['step_order']]);
            }
            DB::commit();

            return response()->json([
                'message' => 'Steps reordered successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to reorder steps',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete a workflow step.
     */
    public function completeStep(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $step = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->first();

        if (!$step) {
            return response()->json(['message' => 'Step not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'public_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $step->status = 'completed';
        $step->notes = ($step->notes ?? '') . "\n\nCompleted on " . now()->toDateTimeString();
        if ($request->has('public_notes')) {
            $step->notes .= "\n\nPublic Notes: " . $request->input('public_notes');
        }
        if ($request->has('internal_notes')) {
            $step->notes .= "\n\nInternal Notes: " . $request->input('internal_notes');
        }
        $step->save();

        return response()->json([
            'message' => 'Step completed successfully',
            'data' => new VisaStepResource($step),
        ]);
    }

    /**
     * Request documents for a step.
     */
    public function requestDocuments(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $step = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->first();

        if (!$step) {
            return response()->json(['message' => 'Step not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'documents' => 'required|array',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.description' => 'nullable|string',
            'documents.*.is_mandatory' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->input('documents') as $doc) {
            \App\Models\VisaStepDocument::create([
                'visa_step_id' => $stepId,
                'document_name' => $doc['name'],
                'description' => $doc['description'] ?? null,
                'is_mandatory' => $doc['is_mandatory'] ?? true,
            ]);
        }

        return response()->json([
            'message' => 'Documents requested successfully',
        ]);
    }

    /**
     * Create a questionnaire for a step.
     */
    public function createQuestionnaire(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        \Log::info('createQuestionnaire called', [
            'visaStatusId' => $visaStatusId,
            'stepId' => $stepId,
            'requestData' => $request->all()
        ]);

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $step = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->first();

        if (!$step) {
            \Log::error('Step not found', [
                'stepId' => $stepId,
                'visaStatusId' => $visaStatusId,
                'employerId' => $employer->id
            ]);
            return response()->json(['message' => 'Step not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'question_type' => 'required|in:text,textarea,select,multiselect,checkbox,radio,date,number,file',
            'options' => 'nullable|array',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'requestData' => $request->all()
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $questionnaire = \App\Models\VisaStepQuestionnaire::create([
                'visa_step_id' => $stepId,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'question_type' => $request->input('question_type'),
                'options' => $request->input('options', []),
                'is_required' => $request->input('is_required', true),
                'sort_order' => $request->input('sort_order', 0),
            ]);

            \Log::info('Questionnaire created successfully', [
                'questionnaireId' => $questionnaire->id,
                'stepId' => $stepId
            ]);

            return response()->json([
                'message' => 'Questionnaire created successfully',
                'data' => $questionnaire,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to create questionnaire', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to create questionnaire: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload employer document for a step.
     */
    public function uploadDocument(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        \Log::info('uploadDocument called', [
            'visaStatusId' => $visaStatusId,
            'stepId' => $stepId,
            'hasFile' => $request->hasFile('file'),
            'requestData' => $request->except('file')
        ]);

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = VisaStatus::where('employer_id', $employer->id)
            ->where('id', $visaStatusId)
            ->first();

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $step = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->first();

        if (!$step) {
            \Log::error('Step not found for upload', [
                'stepId' => $stepId,
                'visaStatusId' => $visaStatusId,
                'employerId' => $employer->id
            ]);
            return response()->json(['message' => 'Step not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_visible_to_seeker' => 'nullable|boolean',
        ]);

        // Convert string boolean to actual boolean for FormData
        if ($request->has('is_visible_to_seeker')) {
            $request->merge([
                'is_visible_to_seeker' => filter_var($request->input('is_visible_to_seeker'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($validator->fails()) {
            \Log::error('Upload validation failed', [
                'errors' => $validator->errors()->toArray(),
                'requestData' => $request->except('file')
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $path = $file->store('visa-employer-uploads', 'public');

            $upload = \App\Models\VisaStepEmployerUpload::create([
                'visa_step_id' => $stepId,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'is_visible_to_seeker' => $request->input('is_visible_to_seeker', true),
                'uploaded_by' => $employer->user_id,
            ]);

            \Log::info('Document uploaded successfully', [
                'uploadId' => $upload->id,
                'stepId' => $stepId,
                'fileName' => $file->getClientOriginalName()
            ]);

            return response()->json([
                'message' => 'Document uploaded successfully',
                'data' => $upload,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to upload document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to upload document: ' . $e->getMessage()
            ], 500);
        }
    }
}
