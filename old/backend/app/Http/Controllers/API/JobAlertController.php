<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobAlertResource;
use App\Models\JobAlert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class JobAlertController extends Controller
{
    /**
     * Display a listing of the user's job alerts.
     */
    public function index(Request $request): JsonResponse
    {
        $alerts = $request->user()
            ->jobAlerts()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => JobAlertResource::collection($alerts),
        ]);
    }

    /**
     * Store a newly created job alert.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'job_title' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'skills' => 'nullable|array',
                'skills.*' => 'string|max:255',
                'frequency' => 'required|in:instant,daily,weekly',
                'is_active' => 'boolean',
            ]);

            // Validation: At least job_title OR industry must be selected
            if (empty($validated['job_title']) && empty($validated['industry'])) {
                throw ValidationException::withMessages([
                    'job_title' => 'Please select either a job title or industry.',
                    'industry' => 'Please select either a job title or industry.',
                ]);
            }

            $alert = $request->user()->jobAlerts()->create($validated);

            return response()->json([
                'data' => new JobAlertResource($alert),
                'message' => 'Job alert created successfully',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create job alert',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display the specified job alert.
     */
    public function show(Request $request, JobAlert $jobAlert): JsonResponse
    {
        // Ensure user can only access their own alerts
        if ($jobAlert->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => new JobAlertResource($jobAlert),
        ]);
    }

    /**
     * Update the specified job alert.
     */
    public function update(Request $request, JobAlert $jobAlert): JsonResponse
    {
        // Ensure user can only update their own alerts
        if ($jobAlert->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'job_title' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'skills' => 'nullable|array',
                'skills.*' => 'string|max:255',
                'frequency' => 'in:instant,daily,weekly',
                'is_active' => 'boolean',
            ]);

            // Validation: At least job_title OR industry must be selected
            if (empty($validated['job_title']) && empty($validated['industry']) && 
                empty($jobAlert->job_title) && empty($jobAlert->industry)) {
                throw ValidationException::withMessages([
                    'job_title' => 'Please select either a job title or industry.',
                    'industry' => 'Please select either a job title or industry.',
                ]);
            }

            $jobAlert->update($validated);

            return response()->json([
                'data' => new JobAlertResource($jobAlert),
                'message' => 'Job alert updated successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update job alert',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Remove the specified job alert.
     */
    public function destroy(Request $request, JobAlert $jobAlert): JsonResponse
    {
        // Ensure user can only delete their own alerts
        if ($jobAlert->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $jobAlert->delete();

            return response()->json([
                'message' => 'Job alert deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete job alert',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Toggle the active status of a job alert.
     */
    public function toggleActive(Request $request, JobAlert $jobAlert): JsonResponse
    {
        // Ensure user can only update their own alerts
        if ($jobAlert->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $jobAlert->update([
                'is_active' => !$jobAlert->is_active,
            ]);

            return response()->json([
                'data' => new JobAlertResource($jobAlert),
                'message' => 'Job alert status updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update job alert status',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }
}
