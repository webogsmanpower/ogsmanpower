<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SeekerAssessmentController extends Controller
{
    /**
     * Get assessments required for a job application.
     */
    public function forJob(int $jobId): JsonResponse
    {
        $job = JobPosting::with(['assessments' => function ($q) {
            $q->with('questions:id,assessment_id,question_text,question_type,options,points,sort_order');
        }])->findOrFail($jobId);

        $seeker = request()->user()->seeker;

        // Get existing attempts for this seeker
        $attempts = AssessmentAttempt::where('seeker_id', $seeker->id)
            ->whereIn('assessment_id', $job->assessments->pluck('id'))
            ->get()
            ->keyBy('assessment_id');

        $assessments = $job->assessments->map(function ($assessment) use ($attempts) {
            $attempt = $attempts->get($assessment->id);
            
            // Don't expose correct answers
            $questions = $assessment->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'question_type' => $q->question_type,
                    'options' => $q->options,
                    'points' => $q->points,
                ];
            });

            return [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'time_limit_minutes' => $assessment->time_limit_minutes,
                'passing_score' => $assessment->passing_score,
                'question_count' => $questions->count(),
                'total_points' => $questions->sum('points'),
                'is_mandatory' => $assessment->pivot->is_mandatory,
                'questions' => $questions,
                'attempt' => $attempt ? [
                    'id' => $attempt->id,
                    'status' => $attempt->status,
                    'score' => $attempt->score,
                    'percentage' => $attempt->percentage,
                    'completed_at' => $attempt->completed_at,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $assessments,
        ]);
    }

    /**
     * Start an assessment attempt.
     */
    public function start(Request $request, int $assessmentId): JsonResponse
    {
        $seeker = $request->user()->seeker;
        $assessment = Assessment::with('questions')->findOrFail($assessmentId);

        // Check if already has an in-progress attempt
        $existingAttempt = AssessmentAttempt::where('seeker_id', $seeker->id)
            ->where('assessment_id', $assessmentId)
            ->where('status', 'in_progress')
            ->first();

        if ($existingAttempt) {
            // Check if expired
            $timeLimit = $assessment->time_limit_minutes;
            $startedAt = $existingAttempt->started_at;
            $expiresAt = $startedAt->addMinutes($timeLimit);

            if (now()->gt($expiresAt)) {
                $existingAttempt->update(['status' => 'expired']);
            } else {
                // Return existing attempt
                return response()->json([
                    'success' => true,
                    'data' => [
                        'attempt_id' => $existingAttempt->id,
                        'started_at' => $existingAttempt->started_at,
                        'expires_at' => $expiresAt,
                        'time_remaining_seconds' => now()->diffInSeconds($expiresAt, false),
                    ],
                ]);
            }
        }

        // Check if already completed/passed
        $completedAttempt = AssessmentAttempt::where('seeker_id', $seeker->id)
            ->where('assessment_id', $assessmentId)
            ->whereIn('status', ['passed', 'failed', 'completed'])
            ->latest()
            ->first();

        if ($completedAttempt) {
            // Check if retry is allowed
            if ($completedAttempt->status === 'failed' && $completedAttempt->retry_allowed) {
                // Reset retry flag and allow new attempt
                $completedAttempt->update(['retry_allowed' => false]);
            } else {
                return response()->json([
                    'error' => $completedAttempt->status === 'failed' 
                        ? 'You have failed this assessment. Please contact the employer or admin to request a retry.'
                        : 'You have already completed this assessment.',
                    'attempt' => [
                        'status' => $completedAttempt->status,
                        'score' => $completedAttempt->score,
                        'percentage' => $completedAttempt->percentage,
                        'retry_allowed' => $completedAttempt->retry_allowed,
                    ],
                ], 422);
            }
        }

        // Check test taker limit for employer
        if ($assessment->employer_id) {
            $employer = $assessment->employer;
            $settings = $employer->getOrCreateSettings();
            if (!$settings->canAddTestTaker()) {
                return response()->json([
                    'error' => 'This assessment has reached its maximum number of participants for this month.',
                ], 422);
            }
            $settings->incrementTestTakers();
        }

        // Create new attempt
        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessmentId,
            'seeker_id' => $seeker->id,
            'job_application_id' => $request->input('job_application_id'),
            'status' => 'in_progress',
            'started_at' => now(),
            'total_points' => $assessment->questions->sum('points'),
        ]);

        $expiresAt = now()->addMinutes($assessment->time_limit_minutes);

        return response()->json([
            'success' => true,
            'message' => 'Assessment started.',
            'data' => [
                'attempt_id' => $attempt->id,
                'started_at' => $attempt->started_at,
                'expires_at' => $expiresAt,
                'time_remaining_seconds' => $assessment->time_limit_minutes * 60,
            ],
        ], 201);
    }

    /**
     * Submit assessment answers.
     */
    public function submit(Request $request, int $attemptId): JsonResponse
    {
        $seeker = $request->user()->seeker;
        $attempt = AssessmentAttempt::where('id', $attemptId)
            ->where('seeker_id', $seeker->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $assessment = $attempt->assessment()->with('questions')->first();

        // Check if expired
        $expiresAt = $attempt->started_at->addMinutes($assessment->time_limit_minutes);
        if (now()->gt($expiresAt)) {
            $attempt->update(['status' => 'expired']);
            return response()->json([
                'error' => 'Time has expired for this assessment.',
            ], 422);
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:assessment_questions,id',
            'answers.*.answer' => 'required|string',
        ]);

        // Calculate score
        $score = 0;
        $answersData = [];

        foreach ($validated['answers'] as $answerData) {
            $question = $assessment->questions->firstWhere('id', $answerData['question_id']);
            if ($question) {
                $isCorrect = $question->isCorrect($answerData['answer']);
                if ($isCorrect) {
                    $score += $question->points;
                }
                $answersData[] = [
                    'question_id' => $question->id,
                    'answer' => $answerData['answer'],
                    'is_correct' => $isCorrect,
                    'points_earned' => $isCorrect ? $question->points : 0,
                ];
            }
        }

        $attempt->score = $score;
        $attempt->answers = $answersData;
        $attempt->calculateResult();
        $attempt->save();

        // Auto-update job application status based on assessment result
        $applicationUpdated = false;
        $applicationStatus = null;
        
        if ($attempt->job_application_id) {
            $application = JobApplication::find($attempt->job_application_id);
            if ($application) {
                if ($attempt->isPassed()) {
                    // Auto-shortlist on pass
                    $application->status = 'shortlisted';
                    $application->assessment_failed = false;
                    $application->save();
                    $applicationUpdated = true;
                    $applicationStatus = 'shortlisted';
                } else {
                    // Auto-reject on fail
                    $application->status = 'rejected';
                    $application->assessment_failed = true;
                    $application->assessment_rejection_reason = 'Failed assessment: ' . $assessment->title . ' (Score: ' . $attempt->percentage . '%)';
                    $application->save();
                    $applicationUpdated = true;
                    $applicationStatus = 'rejected';
                }
            }
        }

        $response = [
            'success' => true,
            'message' => 'Assessment submitted successfully.',
            'data' => [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'score' => $attempt->score,
                'total_points' => $attempt->total_points,
                'percentage' => $attempt->percentage,
                'passed' => $attempt->isPassed(),
                'time_spent_seconds' => $attempt->time_spent_seconds,
                'application_updated' => $applicationUpdated,
                'application_status' => $applicationStatus,
            ],
        ];

        // Include detailed results if show_results is enabled
        if ($assessment->show_results) {
            $response['data']['answers'] = $answersData;
            $response['data']['questions'] = $assessment->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'correct_answer' => $q->correct_answer,
                    'explanation' => $q->explanation,
                ];
            });
        }

        return response()->json($response);
    }

    /**
     * Get attempt result.
     */
    public function result(int $attemptId): JsonResponse
    {
        $seeker = request()->user()->seeker;
        $attempt = AssessmentAttempt::with('assessment')
            ->where('id', $attemptId)
            ->where('seeker_id', $seeker->id)
            ->firstOrFail();

        if ($attempt->status === 'in_progress') {
            return response()->json([
                'error' => 'Assessment is still in progress.',
            ], 422);
        }

        $data = [
            'attempt_id' => $attempt->id,
            'assessment_title' => $attempt->assessment->title,
            'status' => $attempt->status,
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'percentage' => $attempt->percentage,
            'passed' => $attempt->isPassed(),
            'passing_score' => $attempt->assessment->passing_score,
            'started_at' => $attempt->started_at,
            'completed_at' => $attempt->completed_at,
            'time_spent_seconds' => $attempt->time_spent_seconds,
        ];

        if ($attempt->assessment->show_results) {
            $data['answers'] = $attempt->answers;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get all assessment attempts for seeker.
     */
    public function myAttempts(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;

        $attempts = AssessmentAttempt::with('assessment:id,title,type,category')
            ->where('seeker_id', $seeker->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'assessment' => $attempt->assessment,
                    'status' => $attempt->status,
                    'score' => $attempt->score,
                    'percentage' => $attempt->percentage,
                    'passed' => $attempt->isPassed(),
                    'completed_at' => $attempt->completed_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $attempts,
        ]);
    }
}
