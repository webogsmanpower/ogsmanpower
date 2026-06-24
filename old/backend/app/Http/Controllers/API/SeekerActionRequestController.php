<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CandidateActionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SeekerActionRequestController extends Controller
{
    /**
     * Get all action requests for the seeker.
     */
    public function index(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;
        
        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $query = CandidateActionRequest::forSeeker($seeker->id)
            ->with(['employer', 'jobApplication.jobPosting'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->get()->map(function ($req) {
            return [
                'id' => $req->id,
                'employer_name' => $req->employer?->company_name ?? 'Unknown Employer',
                'employer_logo' => $req->employer?->logo_path ? url('storage/' . $req->employer->logo_path) : null,
                'job_title' => $req->jobApplication?->jobPosting?->title ?? null,
                'request_type' => $req->request_type,
                'title' => $req->title,
                'message' => $req->message,
                'is_required' => $req->is_required,
                'due_date' => $req->due_date?->format('Y-m-d'),
                'status' => $req->status,
                'created_at' => $req->created_at,
                'is_overdue' => $req->due_date && $req->due_date->isPast() && $req->status === 'pending',
            ];
        });

        return response()->json(['data' => $requests]);
    }

    /**
     * Get pending action requests count.
     */
    public function pendingCount(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;
        
        if (!$seeker) {
            return response()->json(['count' => 0]);
        }

        $count = CandidateActionRequest::forSeeker($seeker->id)
            ->pending()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get a specific action request.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $seeker = $request->user()->seeker;
        
        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $actionRequest = CandidateActionRequest::forSeeker($seeker->id)
            ->with(['employer', 'jobApplication.jobPosting'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $actionRequest->id,
                'employer_name' => $actionRequest->employer?->company_name ?? 'Unknown Employer',
                'employer_logo' => $actionRequest->employer?->logo_path ? url('storage/' . $actionRequest->employer->logo_path) : null,
                'job_title' => $actionRequest->jobApplication?->jobPosting?->title ?? null,
                'request_type' => $actionRequest->request_type,
                'title' => $actionRequest->title,
                'message' => $actionRequest->message,
                'is_required' => $actionRequest->is_required,
                'due_date' => $actionRequest->due_date?->format('Y-m-d'),
                'status' => $actionRequest->status,
                'response_text' => $actionRequest->response_text,
                'response_file_url' => $actionRequest->response_file_url,
                'responded_at' => $actionRequest->responded_at,
                'created_at' => $actionRequest->created_at,
            ],
        ]);
    }

    /**
     * Respond to an action request.
     */
    public function respond(Request $request, $id): JsonResponse
    {
        $seeker = $request->user()->seeker;
        
        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $actionRequest = CandidateActionRequest::forSeeker($seeker->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $validated = $request->validate([
            'response_text' => 'nullable|string|max:5000',
            'response_file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('response_file')) {
            $file = $request->file('response_file');
            $filePath = $file->store('action-responses/' . $seeker->id, 'public');
        }

        // Validate that at least one response is provided
        if (!$validated['response_text'] && !$filePath) {
            return response()->json([
                'message' => 'Please provide a response text or upload a file',
            ], 422);
        }

        $actionRequest->update([
            'response_text' => $validated['response_text'] ?? null,
            'response_file_path' => $filePath,
            'status' => 'completed',
            'responded_at' => now(),
        ]);

        // TODO: Send notification to employer

        return response()->json([
            'message' => 'Response submitted successfully',
            'data' => [
                'id' => $actionRequest->id,
                'status' => 'completed',
                'responded_at' => $actionRequest->responded_at,
            ],
        ]);
    }
}
