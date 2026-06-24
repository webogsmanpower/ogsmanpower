<?php

namespace App\Console\Commands;

use App\Models\InterviewReminder;
use App\Models\Interview;
use App\Notifications\InterviewReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SendInterviewReminders Command
 * 
 * Sends email reminders for upcoming interviews.
 * This command should be scheduled to run every minute.
 */
class SendInterviewReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'interviews:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders for upcoming interviews';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for pending interview reminders...');

        // Get reminders that are due to be sent
        $pendingReminders = InterviewReminder::pending()
            ->with(['interview.seeker.user', 'interview.employer'])
            ->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($pendingReminders as $reminder) {
            try {
                // Verify interview is still upcoming (not cancelled or rescheduled)
                $interview = $reminder->interview;
                
                if (!$interview || !in_array($interview->status, ['scheduled', 'confirmed'])) {
                    // Mark as sent to avoid retrying for cancelled interviews
                    $reminder->markAsSent();
                    $this->line("Skipped reminder for cancelled/rescheduled interview #{$interview->id}");
                    continue;
                }

                // Send notification to the user
                $user = $reminder->user;
                if ($user && $user->email) {
                    $user->notify(new InterviewReminderNotification($interview, $reminder->reminder_type));
                    
                    // Mark reminder as sent
                    $reminder->markAsSent();
                    $sentCount++;
                    
                    $this->line("Sent reminder to {$user->email} for interview #{$interview->id} ({$reminder->reminder_type})");
                } else {
                    $this->error("No valid user email found for reminder #{$reminder->id}");
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to send reminder #{$reminder->id}: " . $e->getMessage());
                Log::error('Interview reminder failed', [
                    'reminder_id' => $reminder->id,
                    'interview_id' => $reminder->interview_id,
                    'user_id' => $reminder->user_id,
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        $this->info("Reminder sending completed. Sent: {$sentCount}, Failed: {$failedCount}");
        
        return $sentCount > 0 ? 0 : 1;
    }
}
