<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * AdminSkillController
 * 
 * Admin endpoints for skill management including bulk import via TXT/CSV.
 */
class AdminSkillController extends Controller
{
    /**
     * List all skills with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 50);
        $search = $request->input('search', '');
        $category = $request->input('category', '');

        $query = Skill::query();

        if ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        if ($category) {
            $query->where('category', $category);
        }

        $skills = $query->orderByDesc('usage_count')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $skills->items(),
            'meta' => [
                'current_page' => $skills->currentPage(),
                'last_page' => $skills->lastPage(),
                'per_page' => $skills->perPage(),
                'total' => $skills->total(),
            ],
        ]);
    }

    /**
     * Create a single skill
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:skills,name',
            'category' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $skill = Skill::create($validator->validated());
        $this->clearSkillsCache();

        return response()->json([
            'message' => 'Skill created successfully',
            'data' => $skill,
        ], 201);
    }

    /**
     * Update a skill
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:skills,name,' . $id,
            'category' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $skill->update($validator->validated());
        $this->clearSkillsCache();

        return response()->json([
            'message' => 'Skill updated successfully',
            'data' => $skill,
        ]);
    }

    /**
     * Delete a skill
     */
    public function destroy(int $id): JsonResponse
    {
        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        $skill->delete();
        $this->clearSkillsCache();

        return response()->json(['message' => 'Skill deleted successfully']);
    }

    /**
     * Bulk import skills from TXT or CSV file
     * 
     * TXT format: One skill per line
     * CSV format: name,category (header optional)
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt,csv|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($file->getRealPath());
        
        $skills = [];
        $duplicates = [];
        $imported = 0;

        if ($extension === 'txt') {
            // TXT: One skill per line
            $lines = array_filter(array_map('trim', explode("\n", $content)));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $skills[] = ['name' => $line, 'category' => null];
                }
            }
        } else {
            // CSV: name,category format
            $lines = array_filter(array_map('trim', explode("\n", $content)));
            $isFirstLine = true;
            
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                $parts = str_getcsv($line);
                $name = trim($parts[0] ?? '');
                
                // Skip header row if detected
                if ($isFirstLine && strtolower($name) === 'name') {
                    $isFirstLine = false;
                    continue;
                }
                $isFirstLine = false;
                
                if (!empty($name)) {
                    $category = isset($parts[1]) ? trim($parts[1]) : null;
                    $skills[] = ['name' => $name, 'category' => $category ?: null];
                }
            }
        }

        // Bulk insert with duplicate handling
        DB::beginTransaction();
        try {
            foreach ($skills as $skillData) {
                $existing = Skill::where('name', $skillData['name'])->first();
                if ($existing) {
                    $duplicates[] = $skillData['name'];
                    continue;
                }

                Skill::create([
                    'name' => $skillData['name'],
                    'category' => $skillData['category'],
                    'is_active' => true,
                    'usage_count' => 0,
                ]);
                $imported++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }

        $this->clearSkillsCache();

        return response()->json([
            'message' => 'Import completed',
            'data' => [
                'imported' => $imported,
                'duplicates' => count($duplicates),
                'duplicate_names' => array_slice($duplicates, 0, 10), // Show first 10
                'total_processed' => count($skills),
            ],
        ]);
    }

    /**
     * Export all skills as CSV
     */
    public function export(): JsonResponse
    {
        $skills = Skill::orderBy('name')->get(['name', 'category', 'usage_count', 'is_active']);

        return response()->json([
            'data' => $skills,
        ]);
    }

    /**
     * Get skill categories
     */
    public function categories(): JsonResponse
    {
        $categories = Skill::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json(['data' => $categories]);
    }

    /**
     * Clear all skills cache
     */
    private function clearSkillsCache(): void
    {
        Cache::forget('skills_all_active');
        Cache::forget('skills_popular');
        // Clear search cache pattern - this clears all search caches
        // In production, use Redis with pattern delete
    }
}
