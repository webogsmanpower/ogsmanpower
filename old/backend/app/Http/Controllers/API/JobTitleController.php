<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobTitleResource;
use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class JobTitleController extends Controller
{
    /**
     * Display a listing of custom job titles.
     * Only returns titles that were created by users (not static ones).
     */
    public function index(): JsonResponse
    {
        $customTitles = JobTitle::with('creator:id,name')
            ->custom()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => JobTitleResource::collection($customTitles)
        ]);
    }

    /**
     * Store a newly created job title.
     * Uses firstOrCreate to avoid duplicates.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:job_titles,name',
            ]);

            $jobTitle = JobTitle::firstOrCreate([
                'name' => trim($validated['name']),
            ], [
                'created_by' => auth()->id(),
            ]);

            // If the job title already existed, return it with a flag
            $wasExisting = !$jobTitle->wasRecentlyCreated;

            return response()->json([
                'data' => new JobTitleResource($jobTitle->load('creator:id,name')),
                'message' => $wasExisting 
                    ? 'Job title already exists' 
                    : 'Job title created successfully',
                'existing' => $wasExisting,
            ], $wasExisting ? 200 : 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create job title',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Search job titles (both static and custom).
     * This endpoint helps with autocomplete suggestions.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $limit = $validated['limit'] ?? 20;
        $query = trim($validated['query']);

        // Search custom titles
        $customTitles = JobTitle::where('name', 'LIKE', "%{$query}%")
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        return response()->json([
            'data' => JobTitleResource::collection($customTitles),
            'query' => $query,
            'total' => $customTitles->count(),
        ]);
    }
}
