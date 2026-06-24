<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'text', string $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get logo URL
     */
    public static function getLogoUrl()
    {
        $logoPath = static::get('site_logo', null);
        return $logoPath ? asset('storage/' . $logoPath) : null;
    }

    /**
     * Get site title
     */
    public static function getSiteTitle()
    {
        return static::get('site_title', 'Overseas Global Solutions');
    }

    /**
     * Get site description
     */
    public static function getSiteDescription()
    {
        return static::get('site_description', 'Connect with Global Opportunities');
    }
}
