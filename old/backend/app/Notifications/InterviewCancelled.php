<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * InterviewCancelled
 * 
 * Notification sent when an interview is cancelled.
 */
class InterviewCancelled extends Notification
{
    use Queueable;

    public function __construct(
        public Interview $interview,
        public string $reason = 'cancelled'
    ) {}

    /**
     * Get the notification channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->interview->jobApplication->jobPosting;
        $employer = $this->interview->employer;
        
        return (new MailMessage)
            ->subject('Interview Cancelled - ' . $job->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('We regret to inform you that your scheduled interview has been cancelled.')
            ->line('**Position:** ' . $job->title)
            ->line('**Company:** ' . $employer->company_name)
            ->line('**Originally Scheduled:** ' . $this->interview->scheduled_at->format('l, F j, Y g:i A'))
            
            ->when($this->interview->cancellation_reason, function ($message) {
                $message->line('**Reason:** ' . $this->interview->cancellation_reason);
            })
            
            ->line('We apologize for any inconvenience this may cause.')
            ->line('Your application will remain under consideration, and we will contact you if a new interview needs to be scheduled.')
            ->action('View Application Status', route('seeker.applications.show', $this->interview->job_application_id));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $job = $this->interview->jobApplication->jobPosting;
        $employer = $this->interview->employer;
        
        return [
            'type' => 'interview_cancelled',
            'title' => 'Interview Cancelled',
            'message' => "Interview cancelled for {$job->title} at {$employer->company_name}",
            'interview_id' => $this->interview->id,
            'job_application_id' => $this->interview->job_application_id,
            'job_title' => $job->title,
            'company_name' => $employer->company_name,
            'original_scheduled_at' => $this->interview->scheduled_at->toISOString(),
            'cancellation_reason' => $this->interview->cancellation_reason,
            'cancelled_at' => now()->toISOString(),
        ];
    }
}
