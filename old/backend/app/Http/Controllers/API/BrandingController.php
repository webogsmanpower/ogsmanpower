<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class BrandingController extends Controller
{
    /**
     * Get public branding settings
     * This endpoint is publicly accessible (no authentication required)
     */
    public function index(): JsonResponse
    {
        // Cache branding settings for performance - public endpoint hit on every page load
        $branding = Cache::remember('branding_settings', 300, function () {
            return SystemSetting::getBranding();
        });

        return response()->json([
            'success' => true,
            'data' => $branding,
        ]);
    }

    /**
     * Clear branding cache (useful for debugging)
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('branding_settings');
        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Branding cache cleared successfully',
        ]);
    }
}
