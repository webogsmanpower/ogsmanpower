<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * InterviewReminderNotification
 * 
 * Sends email reminders for upcoming interviews.
 */
class InterviewReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Interview $interview,
        private string $reminderType
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $interviewTime = $this->interview->scheduled_at->format('g:i A');
        $interviewDate = $this->interview->scheduled_at->format('l, F j, Y');
        $companyName = $this->interview->employer->company_name;
        $jobTitle = $this->interview->jobApplication->jobPosting->title ?? 'Interview';
        
        $timeFrame = match($this->reminderType) {
            '30_minutes' => '30 minutes',
            '1_hour' => '1 hour',
            '2_hours' => '2 hours',
            '1_day' => '1 day',
            default => 'soon'
        };

        $mailMessage = (new MailMessage)
            ->subject("Interview Reminder: {$jobTitle} at {$companyName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a friendly reminder that you have an interview coming up {$timeFrame}.")
            ->line("**Interview Details:**")
            ->line("• **Position:** {$jobTitle}")
            ->line("• **Company:** {$companyName}")
            ->line("• **Date:** {$interviewDate}")
            ->line("• **Time:** {$interviewTime}")
            ->line("• **Type:** " . ucfirst($this->interview->interview_type));

        // Add location or meeting link
        if ($this->interview->meeting_link) {
            $mailMessage->line("• **Meeting Link:** [Join Interview]({$this->interview->meeting_link})");
        } elseif ($this->interview->location) {
            $mailMessage->line("• **Location:** {$this->interview->location}");
        }

        // Add interview tips based on time frame
        if ($this->reminderType === '1_day') {
            $mailMessage->line("**Tips for tomorrow:**")
                ->line("• Research the company and your interviewers")
                ->line("• Prepare your answers to common questions")
                ->line("• Test your technology if it's a video interview")
                ->line("• Get a good night's sleep!");
        } elseif ($this->reminderType === '1_hour' || $this->reminderType === '30_minutes') {
            $mailMessage->line("**Final checklist:**")
                ->line("• Check your internet connection for video calls")
                ->line("• Have your documents ready")
                ->line("• Join the meeting 5 minutes early")
                ->line("• Take a deep breath - you've got this!");
        }

        $mailMessage->line("Good luck with your interview!")
            ->action('View Interview Details', route('seeker.interviews.show', $this->interview->id))
            ->line("If you need to reschedule or have any questions, please contact the employer directly.")
            ->salutation("Best regards,\nThe OGS Team");

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'interview_id' => $this->interview->id,
            'reminder_type' => $this->reminderType,
            'scheduled_at' => $this->interview->scheduled_at,
        ];
    }
}
