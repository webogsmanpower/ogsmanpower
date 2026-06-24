<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("admin_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }
            return static::castValue($setting->value, $setting->type);
        });
    }

    public static function set(string $key, $value, string $type = 'string', string $category = 'general', ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'category' => $category,
                'description' => $description,
            ]
        );
        Cache::forget("admin_setting_{$key}");
    }

    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'float', 'decimal' => (float) $value,
            default => $value,
        };
    }

    public static function getByCategory(string $category): array
    {
        return static::where('category', $category)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => static::castValue($setting->value, $setting->type)];
            })
            ->toArray();
    }

    public static function getDefaultCustomTestLimit(): int
    {
        return static::get('default_custom_test_limit', 3);
    }

    public static function getDefaultTestTakerLimit(): int
    {
        return static::get('default_test_taker_limit', 50);
    }

    public static function getDefaultScreeningQuestionsLimit(): int
    {
        return static::get('default_screening_questions_limit', 10);
    }

    public static function getDefaultActiveJobLimit(): int
    {
        return static::get('default_active_job_limit', 10);
    }
}
