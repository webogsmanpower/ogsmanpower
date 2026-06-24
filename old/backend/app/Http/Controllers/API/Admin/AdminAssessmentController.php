<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminAssessmentController extends Controller
{
    /**
     * List all assessments (admin + employer custom).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Assessment::with(['creator:id,name', 'employer:id,company_name'])
            ->withCount(['questions', 'attempts']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $assessments = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $assessments,
        ]);
    }

    /**
     * Show a single assessment with full details.
     */
    public function show(int $id): JsonResponse
    {
        $assessment = Assessment::with(['creator:id,name', 'employer:id,company_name', 'questions'])
            ->withCount('attempts')
            ->findOrFail($id);

        // Get pass/fail stats
        $stats = $assessment->attempts()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(percentage) as avg_score
            ")
            ->first();

        $assessment->stats = $stats;

        return response()->json([
            'success' => true,
            'data' => $assessment,
        ]);
    }

    /**
     * Create a standard admin assessment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'time_limit_minutes' => 'required|integer|min:5|max:180',
            'passing_score' => 'required|integer|min:1|max:100',
            'shuffle_questions' => 'boolean',
            'show_results' => 'boolean',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.question_type' => 'required|in:multiple_choice,true_false,text',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.correct_answer' => 'required|string|max:500',
            'questions.*.points' => 'integer|min:1|max:100',
            'questions.*.explanation' => 'nullable|string|max:1000',
        ]);

        $assessment = DB::transaction(function () use ($validated, $request) {
            $assessment = Assessment::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => 'admin_standard',
                'creator_id' => $request->user()->id,
                'employer_id' => null,
                'price' => $validated['price'],
                'time_limit_minutes' => $validated['time_limit_minutes'],
                'passing_score' => $validated['passing_score'],
                'shuffle_questions' => $validated['shuffle_questions'] ?? false,
                'show_results' => $validated['show_results'] ?? true,
                'category' => $validated['category'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['questions'] as $index => $questionData) {
                $assessment->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'options' => $questionData['options'] ?? [],
                    'correct_answer' => $questionData['correct_answer'],
                    'points' => $questionData['points'] ?? 1,
                    'sort_order' => $index,
                    'explanation' => $questionData['explanation'] ?? null,
                ]);
            }

            return $assessment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Standard assessment created successfully.',
            'data' => $assessment->load('questions'),
        ], 201);
    }

    /**
     * Update an assessment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $assessment = Assessment::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'sometimes|numeric|min:0',
            'time_limit_minutes' => 'sometimes|required|integer|min:5|max:180',
            'passing_score' => 'sometimes|required|integer|min:1|max:100',
            'shuffle_questions' => 'boolean',
            'show_results' => 'boolean',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'questions' => 'sometimes|required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.question_type' => 'required|in:multiple_choice,true_false,text',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.correct_answer' => 'required|string|max:500',
            'questions.*.points' => 'integer|min:1|max:100',
            'questions.*.explanation' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($assessment, $validated) {
            $assessment->update(collect($validated)->except('questions')->toArray());

            if (isset($validated['questions'])) {
                $existingIds = $assessment->questions->pluck('id')->toArray();
                $newIds = collect($validated['questions'])->pluck('id')->filter()->toArray();
                
                $toDelete = array_diff($existingIds, $newIds);
                AssessmentQuestion::whereIn('id', $toDelete)->delete();

                foreach ($validated['questions'] as $index => $questionData) {
                    if (!empty($questionData['id'])) {
                        AssessmentQuestion::where('id', $questionData['id'])
                            ->update([
                                'question_text' => $questionData['question_text'],
                                'question_type' => $questionData['question_type'],
                                'options' => $questionData['options'] ?? [],
                                'correct_answer' => $questionData['correct_answer'],
                                'points' => $questionData['points'] ?? 1,
                                'sort_order' => $index,
                                'explanation' => $questionData['explanation'] ?? null,
                            ]);
                    } else {
                        $assessment->questions()->create([
                            'question_text' => $questionData['question_text'],
                            'question_type' => $questionData['question_type'],
                            'options' => $questionData['options'] ?? [],
                            'correct_answer' => $questionData['correct_answer'],
                            'points' => $questionData['points'] ?? 1,
                            'sort_order' => $index,
                            'explanation' => $questionData['explanation'] ?? null,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Assessment updated successfully.',
            'data' => $assessment->fresh()->load('questions'),
        ]);
    }

    /**
     * Delete an assessment.
     */
    public function destroy(int $id): JsonResponse
    {
        $assessment = Assessment::findOrFail($id);
        $assessment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assessment deleted successfully.',
        ]);
    }

    /**
     * Get assessment categories.
     */
    public function categories(): JsonResponse
    {
        $categories = Assessment::whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get assessment statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_assessments' => Assessment::count(),
            'admin_standard' => Assessment::where('type', 'admin_standard')->count(),
            'employer_custom' => Assessment::where('type', 'employer_custom')->count(),
            'total_attempts' => \App\Models\AssessmentAttempt::count(),
            'passed_attempts' => \App\Models\AssessmentAttempt::where('status', 'passed')->count(),
            'failed_attempts' => \App\Models\AssessmentAttempt::where('status', 'failed')->count(),
            'avg_score' => \App\Models\AssessmentAttempt::whereIn('status', ['passed', 'failed'])->avg('percentage'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get all failed assessment attempts (admin view).
     */
    public function failedAttempts(Request $request): JsonResponse
    {
        $query = \App\Models\AssessmentAttempt::with([
            'seeker.user:id,name,email',
            'assessment:id,title,type',
            'jobApplication:id,status,job_posting_id',
            'jobApplication.jobPosting:id,title,employer_id',
            'jobApplication.jobPosting.employer:id,company_name',
        ])
        ->where('status', 'failed')
        ->orderBy('completed_at', 'desc');

        // Filter by assessment
        if ($request->has('assessment_id')) {
            $query->where('assessment_id', $request->assessment_id);
        }

        $attempts = $query->paginate($request->get('per_page', 20));

        $attempts->getCollection()->transform(function ($attempt) {
            return [
                'id' => $attempt->id,
                'seeker' => [
                    'id' => $attempt->seeker->id,
                    'name' => $attempt->seeker->user->name ?? 'Unknown',
                    'email' => $attempt->seeker->user->email ?? '',
                ],
                'assessment' => [
                    'id' => $attempt->assessment->id,
                    'title' => $attempt->assessment->title,
                    'type' => $attempt->assessment->type,
                ],
                'job' => $attempt->jobApplication?->jobPosting ? [
                    'id' => $attempt->jobApplication->jobPosting->id,
                    'title' => $attempt->jobApplication->jobPosting->title,
                    'employer' => $attempt->jobApplication->jobPosting->employer->company_name ?? 'Unknown',
                ] : null,
                'score' => $attempt->score,
                'percentage' => $attempt->percentage,
                'completed_at' => $attempt->completed_at,
                'retry_allowed' => $attempt->retry_allowed,
                'retry_granted_by' => $attempt->retry_granted_by,
                'retry_granted_at' => $attempt->retry_granted_at,
                'application_status' => $attempt->jobApplication?->status,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $attempts,
        ]);
    }

    /**
     * Admin grant retry permission to a seeker.
     */
    public function grantRetry(Request $request, int $attemptId): JsonResponse
    {
        $attempt = \App\Models\AssessmentAttempt::where('id', $attemptId)
            ->where('status', 'failed')
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $attempt->update([
            'retry_allowed' => true,
            'retry_granted_by' => 'admin',
            'retry_granted_by_id' => $request->user()->id,
            'retry_granted_at' => now(),
            'retry_reason' => $validated['reason'] ?? null,
        ]);

        // Reset application status if it was rejected due to assessment failure
        if ($attempt->job_application_id) {
            $application = \App\Models\JobApplication::find($attempt->job_application_id);
            if ($application && $application->assessment_failed) {
                $application->update([
                    'status' => 'pending',
                    'assessment_failed' => false,
                    'assessment_rejection_reason' => null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Retry permission granted by admin.',
        ]);
    }

    /**
     * Admin revoke retry permission.
     */
    public function revokeRetry(Request $request, int $attemptId): JsonResponse
    {
        $attempt = \App\Models\AssessmentAttempt::findOrFail($attemptId);

        $attempt->update([
            'retry_allowed' => false,
            'retry_granted_by' => null,
            'retry_granted_by_id' => null,
            'retry_granted_at' => null,
            'retry_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Retry permission revoked.',
        ]);
    }
}
