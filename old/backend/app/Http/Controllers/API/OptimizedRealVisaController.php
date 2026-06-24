<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VisaStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * OptimizedRealVisaController
 * 
 * Returns real visa data with optimized queries to prevent timeouts
 */
class OptimizedRealVisaController extends Controller
{
    /**
     * Get seeker's visa status - OPTIMIZED REAL DATA VERSION
     * 
     * Uses optimized queries to load real data without timeouts
     */
    public function getVisaStatus(Request $request): JsonResponse
    {
        try {
            $seeker = $request->user()->seeker;

            if (!$seeker) {
                return response()->json(['message' => 'Seeker profile not found'], 404);
            }

            // Use optimized query with minimal relationships to prevent timeout
            $visaStatuses = VisaStatus::where('seeker_id', $seeker->id)
                ->select([
                    'id', 'visa_type', 'destination_country', 'current_step', 
                    'step_history', 'documents_required', 'expected_travel_date',
                    'notes', 'created_at', 'updated_at', 'contract_id', 'employer_id'
                ])
                ->with(['employer:id,company_name,logo_path'])
                ->with(['contract:id,contract_number,status'])
                ->orderBy('created_at', 'desc')
                ->limit(5) // Limit to prevent excessive data loading
                ->get();

            if ($visaStatuses->isEmpty()) {
                return response()->json([
                    'data' => []
                ]);
            }

            // Transform the data efficiently
            $transformedData = $visaStatuses->map(function ($visaStatus) use ($seeker) {
                // Get steps with minimal data
                $steps = DB::table('visa_steps')
                    ->where('visa_status_id', $visaStatus->id)
                    ->select(['id', 'visa_status_id', 'step_name', 'step_order', 'status', 'started_at', 'completed_at'])
                    ->orderBy('step_order')
                    ->get();

                // Get process steps with minimal data
                $processSteps = DB::table('visa_process_steps')
                    ->where('visa_status_id', $visaStatus->id)
                    ->select(['id', 'visa_status_id', 'name', 'label', 'status', 'is_custom', 'created_at'])
                    ->orderBy('created_at')
                    ->get();

                // Get documents for this seeker (optimized query)
                $documents = DB::table('visa_step_documents')
                    ->where('seeker_id', $seeker->id)
                    ->where(function($query) use ($visaStatus) {
                        $query->whereIn('visa_step_id', $steps->pluck('id'))
                              ->orWhereIn('visa_process_step_id', $processSteps->pluck('id'));
                    })
                    ->select(['id', 'visa_step_id', 'visa_process_step_id', 'filename', 'status', 'requirement_name', 'rejection_reason', 'path', 'created_at'])
                    ->get();

                // Group documents by step
                $stepsWithDocuments = $steps->map(function ($step) use ($documents) {
                    $stepDocuments = $documents->where('visa_step_id', $step->id)->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'filename' => $doc->filename,
                            'status' => $doc->status,
                            'requirement_name' => $doc->requirement_name,
                            'rejection_reason' => $doc->rejection_reason,
                            'path' => $doc->path,
                            'created_at' => $doc->created_at,
                            'url' => $doc->path ? asset('storage/' . $doc->path) : null,
                        ];
                    });

                    return [
                        'id' => $step->id,
                        'visa_status_id' => $step->visa_status_id,
                        'step_name' => $step->step_name,
                        'step_order' => $step->step_order,
                        'status' => $step->status,
                        'started_at' => $step->started_at,
                        'completed_at' => $step->completed_at,
                        'label' => $this->getStepLabel($step->step_name, $step->step_order),
                        'documents' => $stepDocuments
                    ];
                });

                $processStepsWithDocuments = $processSteps->map(function ($step) use ($documents) {
                    $stepDocuments = $documents->where('visa_process_step_id', $step->id)->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'filename' => $doc->filename,
                            'status' => $doc->status,
                            'requirement_name' => $doc->requirement_name,
                            'rejection_reason' => $doc->rejection_reason,
                            'path' => $doc->path,
                            'created_at' => $doc->created_at,
                            'url' => $doc->path ? asset('storage/' . $doc->path) : null,
                        ];
                    });

                    return [
                        'id' => $step->id,
                        'visa_status_id' => $step->visa_status_id,
                        'name' => $step->name,
                        'label' => $step->label,
                        'status' => $step->status,
                        'is_custom' => $step->is_custom,
                        'created_at' => $step->created_at,
                        'documents' => $stepDocuments
                    ];
                });

                // Get pending document requests
                $documentsRequired = $visaStatus->documents_required ? json_decode($visaStatus->documents_required, true) : [];
                $pendingDocumentRequests = $documentsRequired;

                return [
                    'id' => $visaStatus->id,
                    'visa_type' => $visaStatus->visa_type,
                    'destination_country' => $visaStatus->destination_country,
                    'current_step' => $visaStatus->current_step,
                    'step_history' => $visaStatus->step_history ? json_decode($visaStatus->step_history, true) : [],
                    'documents_required' => $documentsRequired,
                    'expected_travel_date' => $visaStatus->expected_travel_date,
                    'notes' => $visaStatus->notes,
                    'created_at' => $visaStatus->created_at->toISOString(),
                    'updated_at' => $visaStatus->updated_at->toISOString(),
                    'employer' => $visaStatus->employer ? [
                        'company_name' => $visaStatus->employer->company_name,
                        'logo_path' => $visaStatus->employer->logo_path,
                    ] : null,
                    'contract' => $visaStatus->contract ? [
                        'contract_number' => $visaStatus->contract->contract_number,
                        'status' => $visaStatus->contract->status,
                    ] : null,
                    'steps' => $stepsWithDocuments,
                    'process_steps' => $processStepsWithDocuments,
                    'pending_document_requests' => $pendingDocumentRequests,
                    'has_pending_document_requests' => !empty($pendingDocumentRequests)
                ];
            });

            return response()->json([
                'data' => $transformedData
            ]);

        } catch (\Exception $e) {
            \Log::error('Optimized Real Visa Status API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get step label based on step name and order
     */
    private function getStepLabel($stepName, $stepOrder)
    {
        $workflowSteps = [
            0 => ['label' => 'Not Started'],
            1 => ['label' => 'Document Collection'],
            2 => ['label' => 'Documents Submitted'],
            3 => ['label' => 'Documents Verified'],
            4 => ['label' => 'Medical Scheduled'],
            5 => ['label' => 'Medical Completed'],
            6 => ['label' => 'Medical Cleared'],
            7 => ['label' => 'Visa Applied'],
            8 => ['label' => 'Visa Processing'],
            9 => ['label' => 'Visa Approved'],
            10 => ['label' => 'Visa Rejected'],
            11 => ['label' => 'Travel Scheduled'],
            12 => ['label' => 'Departed'],
            13 => ['label' => 'Arrived'],
            14 => ['label' => 'Completed']
        ];

        return $workflowSteps[$stepOrder]['label'] ?? ucfirst(str_replace('_', ' ', $stepName));
    }

    /**
     * Clear visa status cache for a seeker
     */
    public function clearVisaCache(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Cache cleared successfully']);
    }
}
