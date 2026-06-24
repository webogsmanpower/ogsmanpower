<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterviewResource;
use App\Models\Interview;
use App\Models\InterviewReminder;
use App\Services\InterviewService;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * InterviewController
 * 
 * Handles interview scheduling and management.
 */
class InterviewController extends Controller
{
    public function __construct(
        protected InterviewService $interviewService,
        protected EmployerService $employerService
    ) {}

    /**
     * List all interviews for the employer.
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $filters = $request->only(['status', 'date_from', 'date_to', 'application_id']);
        $perPage = $request->input('per_page', 15);

        $interviews = $this->interviewService->getInterviewsForEmployer($employer, $filters, $perPage);

        return response()->json([
            'data' => InterviewResource::collection($interviews),
            'meta' => [
                'current_page' => $interviews->currentPage(),
                'last_page' => $interviews->lastPage(),
                'per_page' => $interviews->perPage(),
                'total' => $interviews->total(),
            ],
        ]);
    }

    /**
     * Get upcoming interviews.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $interviews = $this->interviewService->getUpcomingInterviews($employer, 10);

        return response()->json([
            'data' => InterviewResource::collection($interviews),
        ]);
    }

    /**
     * Get today's interviews.
     */
    public function today(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $interviews = $this->interviewService->getTodayInterviews($employer);

        return response()->json([
            'data' => InterviewResource::collection($interviews),
        ]);
    }

    /**
     * Schedule a new interview.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_application_id' => 'required|exists:job_applications,id',
            'interview_type' => 'required|in:phone,video,in_person',
            'title' => 'nullable|string|max:255',
            'round_number' => 'nullable|integer|min:1|max:10',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'timezone' => 'nullable|string|max:50',
            'location' => 'required_if:interview_type,in_person|nullable|string|max:255',
            'meeting_link' => 'required_if:interview_type,video|nullable|url|max:500',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:2000',
            'interviewers' => 'nullable|array',
            'interviewers.*' => 'integer|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'questions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $interview = $this->interviewService->scheduleInterview(
            $employer,
            $request->user(),
            $validator->validated()
        );

        if (!$interview) {
            return response()->json([
                'message' => 'Failed to schedule interview. Application may not belong to this employer.',
            ], 422);
        }

        return response()->json([
            'message' => 'Interview scheduled successfully',
            'data' => new InterviewResource($interview->load(['seeker', 'jobApplication.jobPosting'])),
        ], 201);
    }

    /**
     * Get a specific interview.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewById($employer, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        return response()->json([
            'data' => new InterviewResource($interview->load(['seeker.user', 'jobApplication.jobPosting', 'scheduledBy'])),
        ]);
    }

    /**
     * Update an interview.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'interview_type' => 'sometimes|in:phone,video,in_person',
            'title' => 'nullable|string|max:255',
            'round_number' => 'nullable|integer|min:1|max:10',
            'scheduled_at' => 'sometimes|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'timezone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|url|max:500',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:2000',
            'interviewers' => 'nullable|array',
            'interviewers.*' => 'integer|exists:users,id',
            'notes' => 'nullable|string|max:2000',
            'questions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewById($employer, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        $interview = $this->interviewService->updateInterview($interview, $validator->validated());

        return response()->json([
            'message' => 'Interview updated successfully',
            'data' => new InterviewResource($interview),
        ]);
    }

    /**
     * Cancel an interview.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewById($employer, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        $interview->cancel($request->user(), $request->input('reason'));

        return response()->json([
            'message' => 'Interview cancelled successfully',
            'data' => new InterviewResource($interview->fresh()),
        ]);
    }

    /**
     * Mark interview as completed and add feedback.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'feedback' => 'nullable|array',
            'feedback_summary' => 'nullable|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'outcome' => 'required|in:pass,fail,pending,on_hold',
            'recommendation' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewById($employer, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        $interview = $this->interviewService->completeInterview($interview, $validator->validated());

        return response()->json([
            'message' => 'Interview completed successfully',
            'data' => new InterviewResource($interview),
        ]);
    }

    /**
     * Get upcoming interviews for the authenticated seeker.
     */
    public function seekerUpcoming(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $interviews = $this->interviewService->getUpcomingInterviewsForSeeker($seeker);

        return response()->json([
            'data' => InterviewResource::collection($interviews),
        ]);
    }

    /**
     * Get past interviews for the authenticated seeker.
     */
    public function seekerPast(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $interviews = $this->interviewService->getPastInterviewsForSeeker($seeker);

        return response()->json([
            'data' => InterviewResource::collection($interviews),
        ]);
    }

    /**
     * Show specific interview details for the authenticated seeker.
     */
    public function seekerShow(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewForSeeker($seeker, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        return response()->json([
            'data' => new InterviewResource($interview),
        ]);
    }

    /**
     * Set a reminder for an interview.
     */
    public function setReminder(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reminder_type' => 'required|in:1_hour,1_day,2_hours,30_minutes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewForSeeker($seeker, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        $reminderType = $request->input('reminder_type');
        $remindAt = $this->calculateRemindAt($interview->scheduled_at, $reminderType);

        // Remove existing reminder of same type for this interview
        InterviewReminder::where('user_id', $request->user()->id)
            ->where('interview_id', $id)
            ->where('reminder_type', $reminderType)
            ->delete();

        // Create new reminder
        $reminder = InterviewReminder::create([
            'user_id' => $request->user()->id,
            'interview_id' => $id,
            'reminder_type' => $reminderType,
            'remind_at' => $remindAt,
        ]);

        return response()->json([
            'message' => 'Reminder set successfully',
            'data' => [
                'reminder_id' => $reminder->id,
                'reminder_type' => $reminder->reminder_type,
                'remind_at' => $reminder->remind_at,
                'reminder_type_label' => $reminder->reminder_type_label,
            ],
        ]);
    }

    /**
     * Get reminders for an interview.
     */
    public function getReminders(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewForSeeker($seeker, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        $reminders = InterviewReminder::where('user_id', $request->user()->id)
            ->where('interview_id', $id)
            ->get()
            ->map(function ($reminder) {
                return [
                    'id' => $reminder->id,
                    'reminder_type' => $reminder->reminder_type,
                    'reminder_type_label' => $reminder->reminder_type_label,
                    'remind_at' => $reminder->remind_at,
                    'is_sent' => $reminder->is_sent,
                    'sent_at' => $reminder->sent_at,
                ];
            });

        return response()->json([
            'data' => $reminders,
        ]);
    }

    /**
     * Cancel a reminder.
     */
    public function cancelReminder(Request $request, int $id, int $reminderId): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $interview = $this->interviewService->getInterviewForSeeker($seeker, $id);

        if (!$interview) {
            return response()->json(['message' => 'Interview not found'], 404);
        }

        $reminder = InterviewReminder::where('user_id', $request->user()->id)
            ->where('interview_id', $id)
            ->where('id', $reminderId)
            ->first();

        if (!$reminder) {
            return response()->json(['message' => 'Reminder not found'], 404);
        }

        $reminder->delete();

        return response()->json([
            'message' => 'Reminder cancelled successfully',
        ]);
    }

    /**
     * Calculate remind_at timestamp based on reminder type.
     */
    private function calculateRemindAt($scheduledAt, string $reminderType): \Carbon\Carbon
    {
        $scheduledAt = \Carbon\Carbon::parse($scheduledAt);

        return match($reminderType) {
            '30_minutes' => $scheduledAt->subMinutes(30),
            '1_hour' => $scheduledAt->subHour(),
            '2_hours' => $scheduledAt->subHours(2),
            '1_day' => $scheduledAt->subDay(),
            default => $scheduledAt->subHour(), // Default to 1 hour
        };
    }
}
