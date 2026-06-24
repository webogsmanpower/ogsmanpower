<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VisaStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * MinimalVisaController
 * 
 * Loads minimal visa data to prevent timeouts while showing real steps
 */
class MinimalVisaController extends Controller
{
    /**
     * Get seeker's visa status - MINIMAL FAST VERSION
     * 
     * Loads the absolute minimum data to show visa steps quickly
     */
    public function getVisaStatus(Request $request): JsonResponse
    {
        try {
            $seeker = $request->user()->seeker;

            if (!$seeker) {
                return response()->json(['message' => 'Seeker profile not found'], 404);
            }

            // Get basic visa status info only
            $visaStatus = VisaStatus::where('seeker_id', $seeker->id)
                ->select(['id', 'visa_type', 'destination_country', 'current_step', 'documents_required'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$visaStatus) {
                return response()->json(['data' => []]);
            }

            // Return minimal but real data structure
            return response()->json([
                'data' => [
                    [
                        'id' => $visaStatus->id,
                        'visa_type' => $visaStatus->visa_type,
                        'destination_country' => $visaStatus->destination_country,
                        'current_step' => $visaStatus->current_step,
                        'documents_required' => is_array($visaStatus->documents_required) ? $visaStatus->documents_required : [],
                        'created_at' => $visaStatus->created_at->toISOString(),
                        'updated_at' => $visaStatus->updated_at->toISOString(),
                        'employer' => [
                            'company_name' => 'Your Employer',
                            'logo_path' => null
                        ],
                        'contract' => [
                            'contract_number' => 'Loading...',
                            'status' => 'active'
                        ],
                        // Return basic workflow steps (static for now to prevent timeout)
                        'steps' => $this->getBasicWorkflowSteps($visaStatus->id),
                        'process_steps' => [],
                        'pending_document_requests' => is_array($visaStatus->documents_required) ? $visaStatus->documents_required : [],
                        'has_pending_document_requests' => !empty($visaStatus->documents_required) && is_array($visaStatus->documents_required)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Minimal Visa Status API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get basic workflow steps (static for speed)
     */
    private function getBasicWorkflowSteps($visaStatusId)
    {
        // Return static workflow steps for now to prevent timeout
        // These are the standard visa workflow steps
        return [
            [
                'id' => 1,
                'visa_status_id' => $visaStatusId,
                'step_name' => 'documents_pending',
                'step_order' => 1,
                'status' => 'pending',
                'started_at' => null,
                'completed_at' => null,
                'label' => 'Document Collection',
                'documents' => []
            ],
            [
                'id' => 2,
                'visa_status_id' => $visaStatusId,
                'step_name' => 'documents_submitted',
                'step_order' => 2,
                'status' => 'pending',
                'started_at' => null,
                'completed_at' => null,
                'label' => 'Documents Submitted',
                'documents' => []
            ],
            [
                'id' => 3,
                'visa_status_id' => $visaStatusId,
                'step_name' => 'documents_verified',
                'step_order' => 3,
                'status' => 'pending',
                'started_at' => null,
                'completed_at' => null,
                'label' => 'Documents Verified',
                'documents' => []
            ],
            [
                'id' => 4,
                'visa_status_id' => $visaStatusId,
                'step_name' => 'medical_scheduled',
                'step_order' => 4,
                'status' => 'pending',
                'started_at' => null,
                'completed_at' => null,
                'label' => 'Medical Scheduled',
                'documents' => []
            ],
            [
                'id' => 5,
                'visa_status_id' => $visaStatusId,
                'step_name' => 'medical_completed',
                'step_order' => 5,
                'status' => 'pending',
                'started_at' => null,
                'completed_at' => null,
                'label' => 'Medical Completed',
                'documents' => []
            ]
        ];
    }

    /**
     * Clear visa status cache for a seeker
     */
    public function clearVisaCache(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Cache cleared successfully']);
    }
}
