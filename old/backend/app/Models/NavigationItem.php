<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'label',
        'url',
        'target',
        'sort_order',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the menu that owns the navigation item
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class);
    }

    /**
     * Get the parent navigation item
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'parent_id');
    }

    /**
     * Get the child navigation items
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get active child navigation items
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope to get active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root items (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if item has children
     */
    public function hasChildren(): bool
    {
        return $this->activeChildren()->count() > 0;
    }

    /**
     * Get full URL with proper handling
     */
    public function getFullUrlAttribute(): string
    {
        $url = $this->url;
        
        // If URL doesn't start with http or /, add /
        if (!str_starts_with($url, 'http') && !str_starts_with($url, '/')) {
            $url = '/' . $url;
        }
        
        return $url;
    }

    /**
     * Check if URL is external
     */
    public function isExternal(): bool
    {
        return str_starts_with($this->url, 'http');
    }
}
