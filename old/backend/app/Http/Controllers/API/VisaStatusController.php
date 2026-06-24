<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisaStatusResource;
use App\Models\VisaStatus;
use App\Services\VisaService;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * VisaStatusController
 * 
 * Handles visa status tracking and updates.
 */
class VisaStatusController extends Controller
{
    public function __construct(
        protected VisaService $visaService,
        protected EmployerService $employerService
    ) {}

    /**
     * List all visa statuses for the employer.
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $filters = $request->only(['current_step', 'destination_country']);
        $perPage = $request->input('per_page', 15);

        $visaStatuses = $this->visaService->getVisaStatusesForEmployer($employer, $filters, $perPage);

        return response()->json([
            'data' => VisaStatusResource::collection($visaStatuses),
            'meta' => [
                'current_page' => $visaStatuses->currentPage(),
                'last_page' => $visaStatuses->lastPage(),
                'per_page' => $visaStatuses->perPage(),
                'total' => $visaStatuses->total(),
            ],
        ]);
    }

    /**
     * Get a specific visa status.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $visaStatus = $this->visaService->getVisaStatusById($employer, $id);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        return response()->json([
            'data' => new VisaStatusResource($visaStatus->load(['seeker.user', 'contract', 'steps.uploadedDocuments', 'processSteps.documents'])),
        ]);
    }

    /**
     * Update visa step.
     */
    public function updateStep(Request $request, int $id): JsonResponse
    {
        \Log::info('Visa status update attempt:', [
            'visa_id' => $id,
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'user_role' => $request->user()?->role,
            'request_data' => $request->all(),
            'available_steps' => VisaStatus::STEPS
        ]);

        // Check if user is authenticated
        if (!$request->user()) {
            \Log::error('User not authenticated');
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'step' => 'required|in:' . implode(',', VisaStatus::STEPS),
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            \Log::error('Visa status validation failed:', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            \Log::error('Employer not found for user:', ['user_id' => $request->user()?->id]);
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        \Log::info('Employer found:', [
            'employer_id' => $employer->id,
            'employer_verified' => $employer->is_verified,
            'employer_verification_status' => $employer->verification_status
        ]);

        // Check if employer is verified
        if (!$employer->is_verified) {
            \Log::error('Employer not verified:', ['employer_id' => $employer->id, 'verification_status' => $employer->verification_status]);
            return response()->json(['message' => 'Employer account not verified'], 403);
        }

        $visaStatus = $this->visaService->getVisaStatusById($employer, $id);

        if (!$visaStatus) {
            \Log::error('Visa status not found:', ['visa_id' => $id, 'employer_id' => $employer->id]);
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        \Log::info('Visa status found:', [
            'visa_id' => $visaStatus->id,
            'current_step' => $visaStatus->current_step,
            'new_step' => $request->input('step')
        ]);

        $result = $visaStatus->updateStep(
            $request->input('step'),
            $request->user(),
            $request->input('note')
        );

        if (!$result) {
            \Log::error('Invalid step transition:', [
                'from' => $visaStatus->current_step,
                'to' => $request->input('step')
            ]);
            
            return response()->json([
                'message' => 'Invalid step transition',
            ], 422);
        }

        \Log::info('Visa status updated successfully:', [
            'visa_id' => $visaStatus->id,
            'new_step' => $visaStatus->current_step
        ]);

        return response()->json([
            'message' => 'Visa step updated successfully',
            'data' => new VisaStatusResource($visaStatus->fresh()),
        ]);
    }

    /**
     * Update visa details.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'visa_type' => 'sometimes|string|max:100',
            'destination_country' => 'sometimes|string|max:3',
            'origin_country' => 'nullable|string|max:3',
            'medical_date' => 'nullable|date',
            'medical_center' => 'nullable|string|max:255',
            'medical_result' => 'nullable|in:pending,pass,fail,conditional',
            'medical_notes' => 'nullable|string|max:1000',
            'visa_application_date' => 'nullable|date',
            'visa_application_number' => 'nullable|string|max:100',
            'visa_number' => 'nullable|string|max:100',
            'visa_issue_date' => 'nullable|date',
            'visa_expiry_date' => 'nullable|date',
            'visa_rejection_reason' => 'nullable|string|max:500',
            'travel_date' => 'nullable|date',
            'flight_number' => 'nullable|string|max:50',
            'departure_airport' => 'nullable|string|max:10',
            'arrival_airport' => 'nullable|string|max:10',
            'departure_time' => 'nullable|date',
            'arrival_time' => 'nullable|date',
            'actual_arrival_date' => 'nullable|date',
            'accommodation_address' => 'nullable|string|max:500',
            'accommodation_contact' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
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

        $visaStatus = $this->visaService->getVisaStatusById($employer, $id);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        $visaStatus = $this->visaService->updateVisaStatus($visaStatus, $validator->validated(), $request->user());

        return response()->json([
            'message' => 'Visa status updated successfully',
            'data' => new VisaStatusResource($visaStatus),
        ]);
    }

    /**
     * Request documents at any step.
     */
    public function requestDocuments(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.label' => 'required|string|max:255',
            'note' => 'nullable|string|max:500',
            'step' => 'nullable|in:' . implode(',', VisaStatus::STEPS),
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

        $visaStatus = $this->visaService->getVisaStatusById($employer, $id);

        if (!$visaStatus) {
            return response()->json(['message' => 'Visa status not found'], 404);
        }

        try {
            $documents = $request->input('documents');
            $note = $request->input('note');
            $targetStep = $request->input('step', $visaStatus->current_step);

            $result = $visaStatus->requestDocuments($documents, $request->user(), $note, $targetStep);

            if (!$result) {
                return response()->json([
                    'message' => 'Failed to request documents',
                ], 500);
            }

            // Send notification to seeker
            if ($visaStatus->seeker?->user) {
                $visaStatus->seeker->user->notify(
                    new \App\Notifications\VisaDocumentRequestNotification(
                        $documents,
                        $note,
                        $visaStatus->visa_type ?? 'Work Visa',
                        $visaStatus->employer?->company_name
                    )
                );
            }

            return response()->json([
                'message' => 'Documents requested successfully',
                'data' => new VisaStatusResource($visaStatus->fresh()),
            ]);
        } catch (\Exception $e) {
            \Log::error('Document request failed:', [
                'visa_id' => $id,
                'error' => $e->getMessage(),
                'documents' => $request->input('documents'),
            ]);
            
            return response()->json([
                'message' => 'Failed to request documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get visa processing steps.
     */
    public function steps(): JsonResponse
    {
        return response()->json([
            'data' => VisaStatus::STEPS,
        ]);
    }
}
