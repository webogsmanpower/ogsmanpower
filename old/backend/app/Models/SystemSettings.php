<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSettings extends Model
{
    protected $fillable = [
        'key',
        'group',
        'value',
        'type',
        'is_public',
        'description',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'value' => 'string',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get a setting value by key and group
     */
    public static function getValue(string $key, string $group = 'general', $default = null)
    {
        $setting = static::where('key', $key)
            ->where('group', $group)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Get public settings for a group
     */
    public static function getPublicSettings(string $group = 'general')
    {
        return static::where('group', $group)
            ->where('is_public', true)
            ->pluck('value', 'key')
            ->toArray();
    }
}
