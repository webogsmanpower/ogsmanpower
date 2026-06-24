<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Skill Model
 * 
 * Represents a skill that can be used in job postings.
 * Skills are managed by admin and can be imported via TXT/CSV.
 */
class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'is_active',
        'is_custom',
        'created_by',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Scope to filter active skills only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter custom user-created skills only
     * Used by API to return only custom skills (not seeded ones)
     */
    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    /**
     * Scope for fast search by name
     */
    public function scopeSearch($query, string $term)
    {
        if (strlen($term) < 2) {
            return $query->whereRaw('1 = 0'); // Return empty for very short terms
        }

        return $query->where('name', 'LIKE', $term . '%')
            ->orWhere('name', 'LIKE', '%' . $term . '%');
    }

    /**
     * Increment usage count when skill is used
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
