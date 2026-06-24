<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\EmployerSetting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSettingsController extends Controller
{
    /**
     * Get all admin settings.
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->input('category');

        $query = AdminSetting::query();
        if ($category) {
            $query->where('category', $category);
        }

        $settings = $query->orderBy('category')->orderBy('key')->get();

        // Group by category
        $grouped = $settings->groupBy('category')->map(function ($items) {
            return $items->mapWithKeys(function ($item) {
                return [$item->key => [
                    'value' => $this->castValue($item->value, $item->type),
                    'type' => $item->type,
                    'description' => $item->description,
                ]];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Update admin settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $setting) {
            $existing = AdminSetting::where('key', $setting['key'])->first();
            if ($existing) {
                $existing->update(['value' => (string) $setting['value']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
        ]);
    }

    /**
     * Get a single setting.
     */
    public function show(string $key): JsonResponse
    {
        $setting = AdminSetting::where('key', $key)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $setting->key,
                'value' => $this->castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'category' => $setting->category,
                'description' => $setting->description,
            ],
        ]);
    }

    /**
     * Create or update a setting.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'type' => 'required|in:string,integer,boolean,json,float',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $setting = AdminSetting::updateOrCreate(
            ['key' => $validated['key']],
            [
                'value' => is_array($validated['value']) ? json_encode($validated['value']) : (string) $validated['value'],
                'type' => $validated['type'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Setting saved successfully.',
            'data' => $setting,
        ]);
    }

    /**
     * Delete a setting.
     */
    public function destroy(string $key): JsonResponse
    {
        AdminSetting::where('key', $key)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully.',
        ]);
    }

    /**
     * Get employer-specific settings.
     */
    public function employerSettings(int $employerId): JsonResponse
    {
        $settings = EmployerSetting::where('employer_id', $employerId)->first();

        if (!$settings) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No custom settings for this employer. Using defaults.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update employer-specific settings.
     */
    public function updateEmployerSettings(Request $request, int $employerId): JsonResponse
    {
        $validated = $request->validate([
            'custom_test_limit' => 'sometimes|integer|min:0|max:100',
            'test_taker_limit' => 'sometimes|integer|min:0|max:10000',
            'active_job_limit' => 'sometimes|integer|min:0|max:1000',
            'featured_job_limit' => 'sometimes|integer|min:0|max:100',
            'screening_questions_per_job' => 'sometimes|integer|min:0|max:50',
            'assessment_credits' => 'sometimes|numeric|min:0',
            'subscription_tier' => 'sometimes|in:free,basic,professional,enterprise',
        ]);

        $settings = EmployerSetting::updateOrCreate(
            ['employer_id' => $employerId],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Employer settings updated successfully.',
            'data' => $settings,
        ]);
    }

    /**
     * Get all categories.
     */
    public function categories(): JsonResponse
    {
        $categories = AdminSetting::distinct()->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    protected function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Get branding settings for admin view (includes all settings, not just public)
     */
    public function getBranding(): JsonResponse
    {
        $settings = SystemSetting::where('group', SystemSetting::GROUP_BRANDING)->get();

        $branding = [];
        foreach ($settings as $setting) {
            $value = $this->castSystemSettingValue($setting);
            $branding[$setting->key] = [
                'value' => $value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];

            // Add resolved URLs for file types
            if ($setting->type === 'file' && !empty($value)) {
                $branding[$setting->key]['url'] = Storage::disk('public')->url($value);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $branding,
        ]);
    }

    /**
     * Update branding settings (name, colors)
     */
    public function updateBranding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'app_name' => 'sometimes|string|max:255',
            'app_name_ar' => 'sometimes|string|max:255',
            'primary_color' => 'sometimes|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'secondary_color' => 'sometimes|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value, SystemSetting::GROUP_BRANDING, 'string');
        }

        // Clear branding cache
        Cache::forget('branding_settings');
        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Branding settings updated successfully.',
        ]);
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Delete old logo if exists
        $oldLogo = SystemSetting::get('app_logo');
        if ($oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Store new logo
        $path = $request->file('logo')->store('branding', 'public');

        // Update setting
        SystemSetting::set('app_logo', $path, SystemSetting::GROUP_BRANDING, 'file');

        // Clear cache
        Cache::forget('branding_settings');
        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully.',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'favicon' => 'required|image|mimes:png,ico,svg|max:1024',
        ]);

        // Delete old favicon if exists
        $oldFavicon = SystemSetting::get('app_favicon');
        if ($oldFavicon) {
            Storage::disk('public')->delete($oldFavicon);
        }

        // Store new favicon
        $path = $request->file('favicon')->store('branding', 'public');

        // Update setting
        SystemSetting::set('app_favicon', $path, SystemSetting::GROUP_BRANDING, 'file');

        // Clear cache
        Cache::forget('branding_settings');
        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Favicon uploaded successfully.',
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Remove logo
     */
    public function removeLogo(): JsonResponse
    {
        $oldLogo = SystemSetting::get('app_logo');
        if ($oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        SystemSetting::set('app_logo', null, SystemSetting::GROUP_BRANDING, 'file');

        Cache::forget('branding_settings');
        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Logo removed successfully.',
        ]);
    }

    /**
     * Get all system settings (for super admin configuration)
     */
    public function getSystemSettings(Request $request): JsonResponse
    {
        $group = $request->input('group');

        $query = SystemSetting::query();

        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get()->map(function ($setting) {
            $value = $this->castSystemSettingValue($setting);

            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $value,
                'group' => $setting->group,
                'type' => $setting->type,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
                'updated_at' => $setting->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update a system setting
     */
    public function updateSystemSetting(Request $request, int $id): JsonResponse
    {
        $setting = SystemSetting::findOrFail($id);

        $validated = $request->validate([
            'value' => 'nullable',
        ]);

        $value = $validated['value'];

        // Encode JSON values
        if ($setting->type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        $setting->update(['value' => $value]);
        SystemSetting::clearCache();
        Cache::forget('branding_settings');

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully.',
            'data' => $setting->fresh(),
        ]);
    }

    /**
     * Cast system setting value based on type
     */
    protected function castSystemSettingValue(SystemSetting $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true) ?? [],
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }
}
