<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SystemSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    /**
     * Get public branding settings
     */
    public function getBranding(Request $request): JsonResponse
    {
        $branding = SystemSettings::getPublicSettings('branding');
        
        return response()->json([
            'data' => $branding,
            'message' => 'Branding settings retrieved successfully'
        ]);
    }

    /**
     * Get all public settings
     */
    public function getPublicSettings(Request $request): JsonResponse
    {
        $group = $request->input('group', 'general');
        $settings = SystemSettings::getPublicSettings($group);
        
        return response()->json([
            'data' => $settings,
            'message' => 'Settings retrieved successfully'
        ]);
    }

    /**
     * Get a specific setting
     */
    public function getSetting(Request $request, string $key): JsonResponse
    {
        $group = $request->input('group', 'general');
        $setting = SystemSettings::getValue($key, $group);
        
        if ($setting === null) {
            return response()->json([
                'message' => 'Setting not found'
            ], 404);
        }
        
        return response()->json([
            'data' => [
                'key' => $key,
                'value' => $setting,
                'group' => $group
            ],
            'message' => 'Setting retrieved successfully'
        ]);
    }

    /**
     * Update a setting (admin only)
     */
    public function updateSetting(Request $request, string $key): JsonResponse
    {
        $group = $request->input('group', 'general');
        
        $setting = SystemSettings::where('key', $key)
            ->where('group', $group)
            ->firstOrFail();
        
        $validated = $request->validate([
            'value' => 'required|string',
            'type' => 'sometimes|string|in:string,boolean,integer,file',
            'description' => 'sometimes|string|nullable',
            'is_public' => 'sometimes|boolean'
        ]);
        
        $setting->update($validated);
        
        return response()->json([
            'data' => $setting,
            'message' => 'Setting updated successfully'
        ]);
    }
}
