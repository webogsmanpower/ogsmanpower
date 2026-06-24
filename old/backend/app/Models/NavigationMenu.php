<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the navigation items for the menu
     */
    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('sort_order');
    }

    /**
     * Get active navigation items only
     */
    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope to get active menus
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get menu by location
     */
    public static function getByLocation(string $location)
    {
        return static::where('location', $location)->active()->with('activeItems')->first();
    }

    /**
     * Get main navigation
     */
    public static function getMain()
    {
        return static::getByLocation('header');
    }

    /**
     * Get footer navigation
     */
    public static function getFooter()
    {
        return static::getByLocation('footer');
    }
}
