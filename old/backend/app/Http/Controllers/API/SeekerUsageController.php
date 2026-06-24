<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\UsageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SeekerUsageController extends Controller
{
    protected $usageService;

    public function __construct(UsageService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Get bilingual CV limit and pricing info.
     */
    public function getBilingualCVLimit(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $pricing = $this->usageService->getBilingualCVPricing($user);
        
        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * Get usage summary for all features.
     */
    public function getUsageSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $summary = $this->usageService->getUsageSummary($user);
        
        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Check if user can perform a specific action.
     */
    public function checkLimit(Request $request, string $feature): JsonResponse
    {
        $user = $request->user();
        
        $canUse = $this->usageService->canUse($user, $feature);
        $remaining = $this->usageService->getRemaining($user, $feature);
        
        return response()->json([
            'success' => true,
            'data' => [
                'can_use' => $canUse,
                'remaining' => $remaining['remaining'],
                'limit' => $remaining['limit'],
                'used' => $remaining['used'],
            ],
        ]);
    }
}
