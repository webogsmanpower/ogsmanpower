<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * SkillController
 * 
 * Returns ONLY custom user-created skills.
 * Frontend uses STATIC_SKILLS constant for 99% of lookups (zero-latency).
 * This API is for the remaining 1% - custom skills created by users.
 */
class SkillController extends Controller
{
    /**
     * Search custom skills only
     * Returns ONLY user-created skills, NOT seeded skills
     * Frontend has STATIC_SKILLS for instant lookups
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        // Return only custom user-created skills
        $skills = Skill::active()
            ->custom() // CRITICAL: Only custom skills
            ->search($query)
            ->orderByDesc('usage_count')
            ->limit(20)
            ->pluck('name')
            ->toArray();

        return response()->json(['data' => $skills]);
    }

    /**
     * Get all CUSTOM skills only
     * Returns ONLY user-created skills, NOT the 500+ seeded skills
     * Frontend uses STATIC_SKILLS constant for instant lookups
     */
    public function index(Request $request): JsonResponse
    {
        // Return only custom user-created skills (typically 0-50 entries)
        $skills = Skill::active()
            ->custom() // CRITICAL: Only custom skills
            ->orderByDesc('usage_count')
            ->pluck('name')
            ->toArray();

        return response()->json(['data' => $skills]);
    }

    /**
     * Get popular CUSTOM skills
     * Returns only custom user-created skills that are frequently used
     */
    public function popular(): JsonResponse
    {
        $skills = Skill::active()
            ->custom() // CRITICAL: Only custom skills
            ->where('usage_count', '>', 0)
            ->orderByDesc('usage_count')
            ->limit(50)
            ->pluck('name')
            ->toArray();

        return response()->json(['data' => $skills]);
    }

    /**
     * Create a new custom skill
     * Used when user types a skill not in STATIC_SKILLS
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:skills,name',
        ]);

        $skill = Skill::firstOrCreate([
            'name' => trim($validated['name']),
        ], [
            'is_custom' => true,
            'created_by' => auth()->id(),
            'is_active' => true,
        ]);

        return response()->json([
            'data' => $skill->name,
            'message' => $skill->wasRecentlyCreated ? 'Skill created successfully' : 'Skill already exists',
        ], $skill->wasRecentlyCreated ? 201 : 200);
    }
}
