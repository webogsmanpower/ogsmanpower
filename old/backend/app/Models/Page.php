<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'meta_description',
        'meta_keywords',
        'status',
        'template',
        'is_homepage',
        'sort_order',
    ];

    protected $casts = [
        'meta_keywords' => 'json',
        'is_homepage' => 'boolean',
    ];

    /**
     * Get the content blocks for the page
     */
    public function contentBlocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->orderBy('sort_order');
    }

    /**
     * Get active content blocks only
     */
    public function activeContentBlocks(): HasMany
    {
        return $this->contentBlocks()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Scope to get published pages
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get homepage
     */
    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }

    /**
     * Get the homepage
     */
    public static function getHomepage()
    {
        return static::homepage()->published()->with('activeContentBlocks')->first();
    }

    /**
     * Get page by slug
     */
    public static function getBySlug(string $slug)
    {
        return static::where('slug', $slug)->published()->with('activeContentBlocks')->first();
    }

    /**
     * Generate unique slug
     */
    public static function generateUniqueSlug(string $title, int $ignoreId = null): string
    {
        $slug = \Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, function ($query, $ignoreId) {
                return $query->where('id', '!=', $ignoreId);
            })
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Boot model to generate slug automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = static::generateUniqueSlug($page->title);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = static::generateUniqueSlug($page->title, $page->id);
            }
        });
    }
}
