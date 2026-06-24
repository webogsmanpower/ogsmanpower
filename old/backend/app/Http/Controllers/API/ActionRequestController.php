<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CandidateActionRequest;
use App\Models\Seeker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ActionRequestController extends Controller
{
    /**
     * Get all action requests for the employer.
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $request->user()->employer;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $query = CandidateActionRequest::fromEmployer($employer->id)
            ->with(['seeker.user', 'seeker.resume', 'jobApplication.jobPosting', 'createdBy'])
            ->orderBy('created_at', 'desc');

        // Filter by seeker
        if ($request->has('seeker_id')) {
            $query->forSeeker($request->seeker_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        $requests = $query->paginate($request->get('per_page', 15));

        // Transform the data to include response_file_url
        $requests->getCollection()->transform(function ($request) {
            return [
                'id' => $request->id,
                'employer_id' => $request->employer_id,
                'seeker_id' => $request->seeker_id,
                'job_application_id' => $request->job_application_id,
                'request_type' => $request->request_type,
                'title' => $request->title,
                'message' => $request->message,
                'is_required' => $request->is_required,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'response_text' => $request->response_text,
                'response_file_url' => $request->response_file_url,
                'responded_at' => $request->responded_at,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
                'seeker' => [
                    'id' => $request->seeker?->id,
                    'user' => [
                        'name' => $request->seeker?->user?->name,
                        'email' => $request->seeker?->user?->email,
                    ],
                ],
                'job_application' => [
                    'id' => $request->jobApplication?->id,
                    'job_posting' => [
                        'id' => $request->jobApplication?->jobPosting?->id,
                        'title' => $request->jobApplication?->jobPosting?->title,
                    ],
                ],
                'created_by' => [
                    'id' => $request->createdBy?->id,
                    'name' => $request->createdBy?->name,
                ],
            ];
        });

        return response()->json($requests);
    }

    /**
     * Create a new action request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seeker_id' => 'required|exists:seekers,id',
            'job_application_id' => 'nullable|exists:job_applications,id',
            'request_type' => 'required|in:document_upload,answer_question,update_profile',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_required' => 'boolean',
            'due_date' => 'nullable|date|after:today',
        ]);

        $employer = $request->user()->employer;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $actionRequest = CandidateActionRequest::create([
            'employer_id' => $employer->id,
            'seeker_id' => $validated['seeker_id'],
            'job_application_id' => $validated['job_application_id'] ?? null,
            'created_by' => $request->user()->id,
            'request_type' => $validated['request_type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'is_required' => $validated['is_required'] ?? true,
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'pending',
        ]);

        // TODO: Send notification to seeker

        return response()->json([
            'message' => 'Action request created successfully',
            'data' => $actionRequest->load(['seeker.user', 'createdBy']),
        ], 201);
    }

    /**
     * Get a specific action request.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $employer = $request->user()->employer;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $actionRequest = CandidateActionRequest::fromEmployer($employer->id)
            ->with(['seeker.user', 'seeker.resume', 'jobApplication.jobPosting', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $actionRequest->id,
                'employer_id' => $actionRequest->employer_id,
                'seeker_id' => $actionRequest->seeker_id,
                'job_application_id' => $actionRequest->job_application_id,
                'request_type' => $actionRequest->request_type,
                'title' => $actionRequest->title,
                'message' => $actionRequest->message,
                'is_required' => $actionRequest->is_required,
                'due_date' => $actionRequest->due_date,
                'status' => $actionRequest->status,
                'response_text' => $actionRequest->response_text,
                'response_file_url' => $actionRequest->response_file_url,
                'responded_at' => $actionRequest->responded_at,
                'created_at' => $actionRequest->created_at,
                'updated_at' => $actionRequest->updated_at,
                'seeker' => $actionRequest->seeker,
                'job_application' => $actionRequest->jobApplication,
                'created_by' => $actionRequest->createdBy,
            ]
        ]);
    }

    /**
     * Cancel an action request.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $employer = $request->user()->employer;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $actionRequest = CandidateActionRequest::fromEmployer($employer->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $actionRequest->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Action request cancelled',
            'data' => $actionRequest,
        ]);
    }
}
