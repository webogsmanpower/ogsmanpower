<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VisaStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * OptimizedVisaController
 * 
 * High-performance visa status controller with optimized queries
 * and proper caching strategies.
 */
class OptimizedVisaController extends Controller
{
    /**
     * Get seeker's visa status - OPTIMIZED VERSION
     * 
     * Uses raw SQL with proper indexes and caching for maximum performance
     */
    public function getVisaStatus(Request $request): JsonResponse
    {
        try {
            $seeker = $request->user()->seeker;

            if (!$seeker) {
                return response()->json(['message' => 'Seeker profile not found'], 404);
            }

            $cacheKey = "visa_status_{$seeker->id}";
            
            // Cache for 5 minutes to reduce database load
            $visaStatuses = Cache::remember($cacheKey, 300, function () use ($seeker) {
                // Use raw SQL for maximum performance with complex joins
                $visaData = DB::select("
                    SELECT 
                        vs.id,
                        vs.visa_type,
                        vs.destination_country,
                        vs.current_step,
                        vs.step_history,
                        vs.documents_required,
                        vs.expected_travel_date,
                        vs.notes,
                        vs.created_at,
                        vs.updated_at,
                        e.company_name,
                        e.logo_path,
                        c.contract_number,
                        c.status as contract_status
                    FROM visa_statuses vs
                    LEFT JOIN employers e ON vs.employer_id = e.id
                    LEFT JOIN contracts c ON vs.contract_id = c.id
                    WHERE vs.seeker_id = ?
                    ORDER BY vs.created_at DESC
                    LIMIT 10
                ", [$seeker->id]);

                if (empty($visaData)) {
                    return [];
                }

                // Get visa IDs for batch queries
                $visaIds = array_column($visaData, 'id');
                
                // Batch fetch steps with documents (optimized)
                $stepsData = DB::select("
                    SELECT 
                        s.id,
                        s.visa_status_id,
                        s.step_name,
                        s.step_order,
                        s.status,
                        s.started_at,
                        s.completed_at,
                        sd.id as doc_id,
                        sd.filename,
                        sd.status as doc_status,
                        sd.requirement_name,
                        sd.rejection_reason,
                        sd.path,
                        sd.created_at as doc_created_at
                    FROM visa_steps s
                    LEFT JOIN visa_step_documents sd ON s.id = sd.visa_step_id AND sd.seeker_id = ?
                    WHERE s.visa_status_id IN (" . implode(',', $visaIds) . ")
                    ORDER BY s.visa_status_id, s.step_order
                ", [$seeker->id]);

                // Batch fetch process steps with documents
                $processStepsData = DB::select("
                    SELECT 
                        ps.id,
                        ps.visa_status_id,
                        ps.name,
                        ps.label,
                        ps.status,
                        ps.is_custom,
                        ps.created_at,
                        psd.id as doc_id,
                        psd.filename,
                        psd.status as doc_status,
                        psd.requirement_name,
                        psd.rejection_reason,
                        psd.path,
                        psd.created_at as doc_created_at
                    FROM visa_process_steps ps
                    LEFT JOIN visa_step_documents psd ON ps.id = psd.visa_process_step_id AND psd.seeker_id = ?
                    WHERE ps.visa_status_id IN (" . implode(',', $visaIds) . ")
                    ORDER BY ps.visa_status_id, ps.created_at
                ", [$seeker->id]);

                // Organize data by visa status
                $organizedData = [];
                
                // Initialize visa statuses
                foreach ($visaData as $visa) {
                    $organizedData[$visa->id] = [
                        'id' => $visa->id,
                        'visa_type' => $visa->visa_type,
                        'destination_country' => $visa->destination_country,
                        'current_step' => $visa->current_step,
                        'step_history' => json_decode($visa->step_history ?? '[]'),
                        'documents_required' => json_decode($visa->documents_required ?? '[]'),
                        'expected_travel_date' => $visa->expected_travel_date,
                        'notes' => $visa->notes,
                        'created_at' => $visa->created_at,
                        'updated_at' => $visa->updated_at,
                        'employer' => $visa->company_name ? [
                            'company_name' => $visa->company_name,
                            'logo_path' => $visa->logo_path,
                        ] : null,
                        'contract' => $visa->contract_number ? [
                            'contract_number' => $visa->contract_number,
                            'status' => $visa->contract_status,
                        ] : null,
                        'steps' => [],
                        'process_steps' => [],
                    ];
                }

                // Add steps
                $currentSteps = [];
                foreach ($stepsData as $step) {
                    if ($step->doc_id) {
                        if (!isset($currentSteps[$step->visa_status_id])) {
                            $currentSteps[$step->visa_status_id] = [];
                        }
                        
                        $currentSteps[$step->visa_status_id][] = [
                            'id' => $step->id,
                            'visa_status_id' => $step->visa_status_id,
                            'step_name' => $step->step_name,
                            'step_order' => $step->step_order,
                            'status' => $step->status,
                            'started_at' => $step->started_at,
                            'completed_at' => $step->completed_at,
                            'documents' => [[
                                'id' => $step->doc_id,
                                'filename' => $step->filename,
                                'status' => $step->doc_status,
                                'requirement_name' => $step->requirement_name,
                                'rejection_reason' => $step->rejection_reason,
                                'path' => $step->path,
                                'created_at' => $step->doc_created_at,
                            ]],
                        ];
                    }
                }

                // Add process steps
                $currentProcessSteps = [];
                foreach ($processStepsData as $step) {
                    if ($step->doc_id) {
                        if (!isset($currentProcessSteps[$step->visa_status_id])) {
                            $currentProcessSteps[$step->visa_status_id] = [];
                        }
                        
                        $currentProcessSteps[$step->visa_status_id][] = [
                            'id' => $step->id,
                            'visa_status_id' => $step->visa_status_id,
                            'name' => $step->name,
                            'label' => $step->label,
                            'status' => $step->status,
                            'is_custom' => $step->is_custom,
                            'created_at' => $step->created_at,
                            'documents' => [[
                                'id' => $step->doc_id,
                                'filename' => $step->filename,
                                'status' => $step->doc_status,
                                'requirement_name' => $step->requirement_name,
                                'rejection_reason' => $step->rejection_reason,
                                'path' => $step->path,
                                'created_at' => $step->doc_created_at,
                            ]],
                        ];
                    }
                }

                // Merge steps into organized data
                foreach ($currentSteps as $visaId => $steps) {
                    if (isset($organizedData[$visaId])) {
                        $organizedData[$visaId]['steps'] = $steps;
                    }
                }

                foreach ($currentProcessSteps as $visaId => $steps) {
                    if (isset($organizedData[$visaId])) {
                        $organizedData[$visaId]['process_steps'] = $steps;
                    }
                }

                return array_values($organizedData);
            });

            return response()->json([
                'data' => $visaStatuses,
            ]);

        } catch (\Exception $e) {
            \Log::error('Optimized Visa Status API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear visa status cache for a seeker
     */
    public function clearVisaCache(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;
        
        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        Cache::forget("visa_status_{$seeker->id}");
        
        return response()->json(['message' => 'Cache cleared successfully']);
    }
}
