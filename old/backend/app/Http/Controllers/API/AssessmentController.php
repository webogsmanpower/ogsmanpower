<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * List assessments for employer (their custom + all admin standard).
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $request->user()->employer;
        
        $query = Assessment::with(['creator:id,name', 'questions'])
            ->where(function ($q) use ($employer) {
                // Admin standard tests (available to all)
                $q->where('type', 'admin_standard')
                    ->where('is_active', true);
            })
            ->orWhere(function ($q) use ($employer) {
                // Employer's own custom tests
                $q->where('type', 'employer_custom')
                    ->where('employer_id', $employer->id);
            });

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $assessments = $query->orderBy('created_at', 'desc')->get();

        // Add computed attributes
        $assessments->each(function ($assessment) {
            $assessment->question_count = $assessment->questions->count();
            $assessment->total_points = $assessment->questions->sum('points');
        });

        return response()->json([
            'success' => true,
            'data' => $assessments,
        ]);
    }

    /**
     * Get available admin standard tests for browsing.
     */
    public function browseStandard(Request $request): JsonResponse
    {
        $assessments = Assessment::with(['creator:id,name'])
            ->where('type', 'admin_standard')
            ->where('is_active', true)
            ->withCount('questions')
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assessments,
        ]);
    }

    /**
     * Show a single assessment.
     */
    public function show(int $id): JsonResponse
    {
        $assessment = Assessment::with(['creator:id,name', 'questions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $assessment,
        ]);
    }

    /**
     * Create a custom assessment (employer).
     */
    public function store(Request $request): JsonResponse
    {
        $employer = $request->user()->employer;
        $settings = $employer->getOrCreateSettings();

        // Check limit
        if (!$settings->canCreateCustomTest()) {
            return response()->json([
                'error' => "You have reached your limit of {$settings->custom_test_limit} custom tests. Upgrade your plan to create more.",
                'remaining' => 0,
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'time_limit_minutes' => 'required|integer|min:5|max:180',
            'passing_score' => 'required|integer|min:1|max:100',
            'shuffle_questions' => 'boolean',
            'show_results' => 'boolean',
            'category' => 'nullable|string|max:100',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.question_type' => 'required|in:multiple_choice,true_false,text',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.options.*' => 'string|max:500',
            'questions.*.correct_answer' => 'required|string|max:500',
            'questions.*.points' => 'integer|min:1|max:100',
            'questions.*.explanation' => 'nullable|string|max:1000',
        ]);

        $assessment = DB::transaction(function () use ($validated, $request, $employer, $settings) {
            $assessment = Assessment::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'type' => 'employer_custom',
                'creator_id' => $request->user()->id,
                'employer_id' => $employer->id,
                'price' => 0,
                'time_limit_minutes' => $validated['time_limit_minutes'],
                'passing_score' => $validated['passing_score'],
                'shuffle_questions' => $validated['shuffle_questions'] ?? false,
                'show_results' => $validated['show_results'] ?? true,
                'category' => $validated['category'] ?? null,
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

            // Increment counter
            $settings->incrementCustomTests();

            return $assessment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Assessment created successfully.',
            'data' => $assessment->load('questions'),
            'remaining_tests' => $settings->fresh()->remaining_custom_tests,
        ], 201);
    }

    /**
     * Update a custom assessment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $assessment = Assessment::findOrFail($id);
        $employer = $request->user()->employer;

        // Only allow updating own custom tests
        if ($assessment->type !== 'employer_custom' || $assessment->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
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
                // Get existing question IDs
                $existingIds = $assessment->questions->pluck('id')->toArray();
                $newIds = collect($validated['questions'])->pluck('id')->filter()->toArray();
                
                // Delete removed questions
                $toDelete = array_diff($existingIds, $newIds);
                AssessmentQuestion::whereIn('id', $toDelete)->delete();

                // Update or create questions
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
     * Delete a custom assessment.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $assessment = Assessment::findOrFail($id);
        $employer = $request->user()->employer;

        // Only allow deleting own custom tests
        if ($assessment->type !== 'employer_custom' || $assessment->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $settings = $employer->getOrCreateSettings();
        $assessment->delete();
        $settings->decrementCustomTests();

        return response()->json([
            'success' => true,
            'message' => 'Assessment deleted successfully.',
        ]);
    }

    /**
     * Attach an assessment to a job.
     */
    public function attachToJob(Request $request, int $jobId): JsonResponse
    {
        $job = JobPosting::findOrFail($jobId);
        $employer = $request->user()->employer;

        if ($job->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'assessment_id' => 'required|integer|exists:assessments,id',
            'is_mandatory' => 'boolean',
        ]);

        $assessment = Assessment::findOrFail($validated['assessment_id']);

        // Check if it's an admin paid test
        $pricePaid = 0;
        if ($assessment->isAdminTest() && $assessment->isPaid()) {
            $settings = $employer->getOrCreateSettings();
            if (!$settings->deductCredits($assessment->price)) {
                return response()->json([
                    'error' => 'Insufficient credits. Please purchase more credits to use this test.',
                    'required' => $assessment->price,
                    'available' => $settings->assessment_credits,
                ], 422);
            }
            $pricePaid = $assessment->price;
        }

        // Attach to job
        $job->assessments()->syncWithoutDetaching([
            $assessment->id => [
                'is_mandatory' => $validated['is_mandatory'] ?? true,
                'price_paid' => $pricePaid,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assessment attached to job successfully.',
            'data' => $job->assessments,
        ]);
    }

    /**
     * Detach an assessment from a job.
     */
    public function detachFromJob(Request $request, int $jobId, int $assessmentId): JsonResponse
    {
        $job = JobPosting::findOrFail($jobId);
        $employer = $request->user()->employer;

        if ($job->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $job->assessments()->detach($assessmentId);

        return response()->json([
            'success' => true,
            'message' => 'Assessment removed from job.',
        ]);
    }

    /**
     * Get employer's usage limits and stats.
     */
    public function limits(Request $request): JsonResponse
    {
        $employer = $request->user()->employer;
        $settings = $employer->getOrCreateSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'custom_test_limit' => $settings->custom_test_limit,
                'custom_tests_created' => $settings->custom_tests_created,
                'remaining_custom_tests' => $settings->remaining_custom_tests,
                'test_taker_limit' => $settings->test_taker_limit,
                'test_takers_this_month' => $settings->test_takers_this_month,
                'remaining_test_takers' => $settings->remaining_test_takers,
                'assessment_credits' => $settings->assessment_credits,
                'subscription_tier' => $settings->subscription_tier,
            ],
        ]);
    }

    /**
     * Get failed assessment attempts for a job (for retry permission management).
     */
    public function failedAttempts(Request $request, int $jobId): JsonResponse
    {
        $employer = $request->user()->employer;
        $job = JobPosting::findOrFail($jobId);

        if ($job->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $assessmentIds = $job->assessments()->pluck('assessments.id');

        $failedAttempts = AssessmentAttempt::with(['seeker.user:id,name,email', 'assessment:id,title', 'jobApplication:id,status'])
            ->whereIn('assessment_id', $assessmentIds)
            ->where('status', 'failed')
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(function ($attempt) {
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
                    ],
                    'score' => $attempt->score,
                    'percentage' => $attempt->percentage,
                    'completed_at' => $attempt->completed_at,
                    'retry_allowed' => $attempt->retry_allowed,
                    'retry_granted_at' => $attempt->retry_granted_at,
                    'application_status' => $attempt->jobApplication?->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $failedAttempts,
        ]);
    }

    /**
     * Grant retry permission to a seeker for a failed assessment.
     */
    public function grantRetry(Request $request, int $attemptId): JsonResponse
    {
        $employer = $request->user()->employer;
        
        $attempt = AssessmentAttempt::with('assessment.jobs')
            ->where('id', $attemptId)
            ->where('status', 'failed')
            ->firstOrFail();

        // Verify employer owns a job that uses this assessment
        $hasAccess = $attempt->assessment->jobs()
            ->where('employer_id', $employer->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $attempt->update([
            'retry_allowed' => true,
            'retry_granted_by' => 'employer',
            'retry_granted_by_id' => $employer->id,
            'retry_granted_at' => now(),
            'retry_reason' => $validated['reason'] ?? null,
        ]);

        // Reset application status if it was rejected due to assessment failure
        if ($attempt->job_application_id) {
            $application = JobApplication::find($attempt->job_application_id);
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
            'message' => 'Retry permission granted. The candidate can now retake the assessment.',
        ]);
    }

    /**
     * Revoke retry permission from a seeker.
     */
    public function revokeRetry(Request $request, int $attemptId): JsonResponse
    {
        $employer = $request->user()->employer;
        
        $attempt = AssessmentAttempt::with('assessment.jobs')
            ->where('id', $attemptId)
            ->firstOrFail();

        // Verify employer owns a job that uses this assessment
        $hasAccess = $attempt->assessment->jobs()
            ->where('employer_id', $employer->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

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
