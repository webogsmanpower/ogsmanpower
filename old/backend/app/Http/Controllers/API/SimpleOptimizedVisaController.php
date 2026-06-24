<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VisaStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * SimpleOptimizedVisaController
 * 
 * Simple optimization with caching for the existing visa status logic
 */
class SimpleOptimizedVisaController extends Controller
{
    /**
     * Get seeker's visa status - SIMPLE OPTIMIZED VERSION
     * 
     * Uses the existing logic but with caching
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
                // Use the existing optimized query from SeekerContractController
                $visaStatuses = \App\Models\VisaStatus::where('seeker_id', $seeker->id)
                    ->with(['employer:id,company_name,logo_path', 'contract:id,contract_number,status'])
                    ->with(['steps' => function($query) {
                        $query->select('id', 'visa_status_id', 'step_name', 'step_order', 'status', 'started_at', 'completed_at')
                              ->with(['documents' => function($docQuery) {
                                  $docQuery->select('id', 'visa_step_id', 'filename', 'status', 'created_at', 'requirement_name', 'rejection_reason', 'path')
                                         ->where('seeker_id', request()->user()->seeker->id);
                              }]);
                    }])
                    ->with(['processSteps' => function($query) {
                        $query->select('id', 'visa_status_id', 'name', 'label', 'status', 'is_custom', 'created_at')
                              ->with(['documents' => function($docQuery) {
                                  $docQuery->select('id', 'visa_process_step_id', 'filename', 'status', 'created_at', 'requirement_name', 'rejection_reason', 'path')
                                         ->where('seeker_id', request()->user()->seeker->id);
                              }]);
                    }])
                    ->orderBy('created_at', 'desc')
                    ->limit(10) // Limit to prevent excessive data loading
                    ->get();

                return \App\Http\Resources\VisaStatusResource::collection($visaStatuses);
            });

            return response()->json([
                'data' => $visaStatuses,
            ]);

        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Simple Optimized Visa Status API Error: ' . $e->getMessage(), [
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
