<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    const CACHE_KEY = 'system_settings';
    const CACHE_TTL = 3600; // 1 hour

    const GROUP_BRANDING = 'branding';
    const GROUP_GENERAL = 'general';
    const GROUP_SECURITY = 'security';

    /**
     * Get all settings as a keyed array
     */
    public static function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::all()->mapWithKeys(function ($setting) {
                return [$setting->key => self::castValue($setting)];
            })->toArray();
        });
    }

    /**
     * Get a single setting value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::getAll();
        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'group' => $group,
                'type' => $type,
            ]
        );

        self::clearCache();
    }

    /**
     * Get branding settings for public API
     */
    public static function getBranding(): array
    {
        $settings = self::where('group', self::GROUP_BRANDING)
            ->where('is_public', true)
            ->get();

        $branding = [];
        foreach ($settings as $setting) {
            $branding[$setting->key] = self::castValue($setting);
        }

        // Apply defaults if not set
        $branding['app_name'] = $branding['app_name'] ?? 'Overseas Jobs';
        $branding['app_name_ar'] = $branding['app_name_ar'] ?? 'وظائف الخارج';

        // Resolve logo URL if it's a file path
        if (!empty($branding['app_logo'])) {
            $branding['app_logo_url'] = self::resolveFileUrl($branding['app_logo']);
        }

        if (!empty($branding['app_favicon'])) {
            $branding['app_favicon_url'] = self::resolveFileUrl($branding['app_favicon']);
        }

        return $branding;
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(self $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true) ?? [],
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    /**
     * Resolve file path to full URL
     */
    protected static function resolveFileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // If it's a data URI, return as-is
        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        // For storage paths, generate URL
        return Storage::disk('public')->url($path);
    }

    /**
     * Clear the settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Boot method to clear cache on save
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }
}
