<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\JobScreeningQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ScreeningController extends Controller
{
    /**
     * Get screening questions for a job.
     */
    public function index(int $jobId): JsonResponse
    {
        $job = JobPosting::findOrFail($jobId);
        
        $questions = $job->screeningQuestions()
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    /**
     * Store screening questions for a job (bulk create/update).
     */
    public function store(Request $request, int $jobId): JsonResponse
    {
        $job = JobPosting::findOrFail($jobId);
        
        // Check employer ownership
        $employer = $request->user()->employer;
        if (!$employer || $job->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check limit
        $settings = $employer->getOrCreateSettings();
        $questionsCount = count($request->input('questions', []));
        if ($questionsCount > $settings->screening_questions_per_job) {
            return response()->json([
                'error' => "Maximum {$settings->screening_questions_per_job} screening questions allowed per job.",
            ], 422);
        }

        $validated = $request->validate([
            'questions' => 'required|array',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.question_type' => 'required|in:text,yes_no,multiple_choice',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'string|max:255',
            'questions.*.is_required' => 'boolean',
        ]);

        DB::transaction(function () use ($job, $validated) {
            // Delete existing questions
            $job->screeningQuestions()->delete();

            // Create new questions
            foreach ($validated['questions'] as $index => $questionData) {
                $job->screeningQuestions()->create([
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'options' => $questionData['options'] ?? null,
                    'is_required' => $questionData['is_required'] ?? true,
                    'sort_order' => $index,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Screening questions saved successfully.',
            'data' => $job->screeningQuestions()->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Update a single screening question.
     */
    public function update(Request $request, int $questionId): JsonResponse
    {
        $question = JobScreeningQuestion::findOrFail($questionId);
        $job = $question->jobPosting;
        
        // Check employer ownership
        $employer = $request->user()->employer;
        if (!$employer || $job->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'question_text' => 'sometimes|required|string|max:1000',
            'question_type' => 'sometimes|required|in:text,yes_no,multiple_choice',
            'options' => 'nullable|array',
            'options.*' => 'string|max:255',
            'is_required' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data' => $question->fresh(),
        ]);
    }

    /**
     * Delete a screening question.
     */
    public function destroy(Request $request, int $questionId): JsonResponse
    {
        $question = JobScreeningQuestion::findOrFail($questionId);
        $job = $question->jobPosting;
        
        // Check employer ownership
        $employer = $request->user()->employer;
        if (!$employer || $job->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
        ]);
    }

    /**
     * Reorder screening questions.
     */
    public function reorder(Request $request, int $jobId): JsonResponse
    {
        $job = JobPosting::findOrFail($jobId);
        
        // Check employer ownership
        $employer = $request->user()->employer;
        if (!$employer || $job->employer_id !== $employer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:job_screening_questions,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $questionId) {
                JobScreeningQuestion::where('id', $questionId)
                    ->update(['sort_order' => $index]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Questions reordered successfully.',
        ]);
    }
}
