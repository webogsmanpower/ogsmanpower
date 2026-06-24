<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\Request;

/**
 * AdminJobController
 * 
 * Handles job moderation for admins.
 * Includes view all jobs and delete inappropriate ones.
 */
class AdminJobController extends Controller
{
    /**
     * List all jobs with optional filters.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = JobPosting::with(['employer:id,company_name,is_verified,verification_status']);

        // Search by title or company
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('employer', function ($eq) use ($search) {
                        $eq->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['draft', 'published', 'closed', 'archived'])) {
            $query->where('status', $request->status);
        }

        // Filter by employer verification status
        if ($request->has('employer_verified')) {
            $query->whereHas('employer', function ($q) use ($request) {
                $q->where('is_verified', $request->boolean('employer_verified'));
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $jobs = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * Get job details.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $job = JobPosting::with([
            'employer:id,company_name,is_verified,verification_status,logo_path',
            'applications' => function ($q) {
                $q->select('id', 'job_posting_id', 'status', 'created_at')
                    ->latest()
                    ->limit(10);
            },
        ])->findOrFail($id);

        $stats = [
            'total_applications' => $job->applications()->count(),
            'pending_applications' => $job->applications()->where('status', 'pending')->count(),
            'views' => $job->views_count ?? 0,
        ];

        return response()->json([
            'job' => $job,
            'stats' => $stats,
        ]);
    }

    /**
     * Delete a job (moderation action).
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $job = JobPosting::findOrFail($id);

        // Store deletion info before deleting
        $deletionLog = [
            'job_id' => $job->id,
            'job_title' => $job->title,
            'employer_id' => $job->employer_id,
            'deleted_by' => $request->user()->id,
            'reason' => $request->reason,
            'deleted_at' => now(),
        ];

        // TODO: Log this to an audit table
        // AuditLog::create($deletionLog);

        // Soft delete the job
        $job->delete();

        return response()->json([
            'message' => 'Job deleted successfully.',
            'deletion_log' => $deletionLog,
        ]);
    }

    /**
     * Update job status (moderation action).
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,published,closed,archived',
            'reason' => 'nullable|string|max:500',
        ]);

        $job = JobPosting::findOrFail($id);
        $oldStatus = $job->status;

        $job->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Job status updated.',
            'job' => $job->fresh(),
            'previous_status' => $oldStatus,
        ]);
    }
}
