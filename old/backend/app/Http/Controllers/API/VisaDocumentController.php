<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisaStepResource;
use App\Models\VisaStatus;
use App\Models\VisaStep;
use App\Models\VisaStepDocument;
use App\Models\VisaProcessStep;
use App\Services\VisaService;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * VisaDocumentController
 * 
 * Handles document verification and custom document requests for employers.
 */
class VisaDocumentController extends Controller
{
    public function __construct(
        protected VisaService $visaService,
        protected EmployerService $employerService
    ) {}

    /**
     * Get documents for a specific visa step.
     */
    public function index(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = $this->visaService->getVisaStatusById($employer, $visaStatusId);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $visaStep = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        $documents = $visaStep->documents()->with('seeker.user')->get();

        return response()->json([
            'data' => $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'filename' => $document->filename,
                    'status' => $document->status,
                    'rejection_reason' => $document->rejection_reason,
                    'url' => $document->getUrl(),
                    'uploaded_at' => $document->created_at->toIso8601String(),
                    'seeker' => [
                        'id' => $document->seeker->id,
                        'name' => $document->seeker->user->name,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Get documents for a custom process step.
     */
    public function processIndex(Request $request, int $visaStatusId, int $processStepId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = $this->visaService->getVisaStatusById($employer, $visaStatusId);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $processStep = VisaProcessStep::where('id', $processStepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        $documents = $processStep->documents()->with('seeker.user')->get();

        return response()->json([
            'data' => $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'filename' => $document->filename,
                    'status' => $document->status,
                    'rejection_reason' => $document->rejection_reason,
                    'url' => $document->getUrl(),
                    'uploaded_at' => $document->created_at->toIso8601String(),
                    'seeker' => [
                        'id' => $document->seeker->id,
                        'name' => $document->seeker->user->name,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Verify a document.
     */
    public function verify(Request $request, int $visaStatusId, int $stepId, int $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,rejected',
            'reason' => 'required_if:status,rejected|string|max:500',
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

        $visaStatus = $this->visaService->getVisaStatusById($employer, $visaStatusId);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $visaStep = VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        $document = $visaStep->documents()
            ->where('id', $documentId)
            ->firstOrFail();

        $status = $request->input('status');
        $reason = $request->input('reason');

        if ($status === 'verified') {
            $document->verify();
        } else {
            $document->reject($reason);
        }

        // Send notification to seeker
        if ($status === 'rejected' && $visaStatus->seeker && $visaStatus->seeker->user) {
            $visaStatus->seeker->user->notify(
                new \App\Notifications\VisaDocumentRejectedNotification(
                    $visaStatus,
                    $visaStep,
                    $document,
                    $employer,
                    $reason
                )
            );
        }

        return response()->json([
            'message' => "Document {$status} successfully",
            'data' => [
                'id' => $document->id,
                'status' => $document->status,
                'rejection_reason' => $document->rejection_reason,
            ],
        ]);
    }

    /**
     * Verify a document for a custom process step.
     */
    public function processVerify(Request $request, int $visaStatusId, int $processStepId, int $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,rejected',
            'reason' => 'required_if:status,rejected|string|max:500|nullable',
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

        $visaStatus = $this->visaService->getVisaStatusById($employer, $visaStatusId);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $processStep = VisaProcessStep::where('id', $processStepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        $document = $processStep->documents()
            ->where('id', $documentId)
            ->firstOrFail();

        $status = $request->input('status');
        $reason = $request->input('reason');

        if ($status === 'verified') {
            $document->verify();
        } else {
            $document->reject($reason);
        }

        if ($status === 'rejected' && $visaStatus->seeker && $visaStatus->seeker->user) {
            $visaStatus->seeker->user->notify(
                new \App\Notifications\VisaDocumentRejectedNotification(
                    $visaStatus,
                    $processStep,
                    $document,
                    $employer,
                    $reason
                )
            );
        }

        return response()->json([
            'message' => "Document {$status} successfully",
            'data' => [
                'id' => $document->id,
                'status' => $document->status,
                'rejection_reason' => $document->rejection_reason,
            ],
        ]);
    }

    /**
     * Request a custom document.
     */
    public function requestCustom(Request $request, int $visaStatusId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
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

        $visaStatus = $this->visaService->getVisaStatusById($employer, $visaStatusId);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        // Create custom process step
        $processStep = VisaProcessStep::create([
            'visa_status_id' => $visaStatusId,
            'name' => $request->input('name'),
            'label' => $request->input('label'),
            'status' => 'pending',
            'is_custom' => true,
        ]);

        // Notify seeker
        if ($visaStatus->seeker && $visaStatus->seeker->user) {
            $visaStatus->seeker->user->notify(
                new \App\Notifications\VisaDocumentRequestedNotification(
                    $visaStatus,
                    $processStep,
                    $employer
                )
            );
        }

        return response()->json([
            'message' => 'Custom document requested successfully',
            'data' => [
                'id' => $processStep->id,
                'name' => $processStep->name,
                'label' => $processStep->label,
                'status' => $processStep->status,
                'is_custom' => $processStep->is_custom,
            ],
        ]);
    }

    /**
     * Get custom process steps for a visa status.
     */
    public function getCustomSteps(Request $request, int $visaStatusId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = $this->visaService->getVisaStatusById($employer, $visaStatusId);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $customSteps = $visaStatus->processSteps()->custom()->get();

        return response()->json([
            'data' => $customSteps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'name' => $step->name,
                    'label' => $step->label,
                    'status' => $step->status,
                    'created_at' => $step->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Delete a custom process step.
     */
    public function deleteProcessStep(Request $request, int $visaStatusId, int $processStepId): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = $this->visaService->getVisaStatusById($employer, $visaStatusId);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $processStep = VisaProcessStep::where('id', $processStepId)
            ->where('visa_status_id', $visaStatusId)
            ->where('is_custom', true)
            ->firstOrFail();

        // Delete associated documents
        $processStep->documents()->delete();
        
        // Delete the process step
        $processStep->delete();

        return response()->json([
            'message' => 'Document request removed successfully',
        ]);
    }
}
