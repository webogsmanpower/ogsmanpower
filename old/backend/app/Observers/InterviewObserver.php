<?php

namespace App\Observers;

use App\Models\Interview;
use App\Models\User;
use App\Notifications\InterviewScheduled;
use App\Notifications\InterviewUpdated;
use App\Notifications\InterviewCancelled;
use Illuminate\Support\Facades\Log;

/**
 * InterviewObserver
 * 
 * Handles interview-related events and notifications.
 */
class InterviewObserver
{
    /**
     * Handle the Interview "created" event.
     */
    public function created(Interview $interview): void
    {
        try {
            // Send notification to seeker
            $seeker = $interview->seeker;
            if ($seeker && $seeker->user) {
                $seeker->user->notify(new InterviewScheduled($interview));
                Log::info('Interview scheduled notification sent', [
                    'interview_id' => $interview->id,
                    'seeker_id' => $seeker->id,
                    'user_id' => $seeker->user->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send interview scheduled notification', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Interview "updated" event.
     */
    public function updated(Interview $interview): void
    {
        try {
            // Check if interview was rescheduled
            if ($interview->wasChanged('scheduled_at') && $interview->status === 'rescheduled') {
                $seeker = $interview->seeker;
                if ($seeker && $seeker->user) {
                    $seeker->user->notify(new InterviewUpdated($interview, 'rescheduled'));
                    Log::info('Interview rescheduled notification sent', [
                        'interview_id' => $interview->id,
                        'seeker_id' => $seeker->id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send interview updated notification', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Interview "deleted" event.
     */
    public function deleted(Interview $interview): void
    {
        try {
            $seeker = $interview->seeker;
            if ($seeker && $seeker->user) {
                $seeker->user->notify(new InterviewCancelled($interview, 'deleted'));
                Log::info('Interview cancelled notification sent', [
                    'interview_id' => $interview->id,
                    'seeker_id' => $seeker->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send interview cancelled notification', [
                'interview_id' => $interview->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
