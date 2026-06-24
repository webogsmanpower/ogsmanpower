<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * InterviewScheduled
 * 
 * Notification sent to seekers when an interview is scheduled.
 */
class InterviewScheduled extends Notification
{
    use Queueable;

    public function __construct(
        public Interview $interview
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
            ->subject('Interview Scheduled - ' . $job->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! An interview has been scheduled for your application.')
            ->line('**Position:** ' . $job->title)
            ->line('**Company:** ' . $employer->company_name)
            ->line('**Date:** ' . $this->interview->scheduled_at->format('l, F j, Y'))
            ->line('**Time:** ' . $this->interview->scheduled_at->format('g:i A') . ' ' . $this->interview->timezone)
            ->line('**Duration:** ' . $this->interview->duration_minutes . ' minutes')
            ->line('**Type:** ' . ucfirst(str_replace('_', ' ', $this->interview->interview_type)))
            
            ->when($this->interview->location, function ($message) {
                $message->line('**Location:** ' . $this->interview->location);
            })
            
            ->when($this->interview->meeting_link, function ($message) {
                $message->line('**Meeting Link:** ' . $this->interview->meeting_link);
            })
            
            ->when($this->interview->instructions, function ($message) {
                $message->line('**Instructions:** ' . $this->interview->instructions);
            })
            
            ->action('View Interview Details', route('seeker.interviews.show', $this->interview->id))
            ->line('Please confirm your attendance by logging into your portal.')
            ->line('Good luck with your interview!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $job = $this->interview->jobApplication->jobPosting;
        $employer = $this->interview->employer;
        
        return [
            'type' => 'interview_scheduled',
            'title' => 'Interview Scheduled',
            'message' => "Interview scheduled for {$job->title} at {$employer->company_name}",
            'interview_id' => $this->interview->id,
            'job_application_id' => $this->interview->job_application_id,
            'job_title' => $job->title,
            'company_name' => $employer->company_name,
            'scheduled_at' => $this->interview->scheduled_at->toISOString(),
            'interview_type' => $this->interview->interview_type,
            'duration_minutes' => $this->interview->duration_minutes,
            'location' => $this->interview->location,
            'meeting_link' => $this->interview->meeting_link,
            'instructions' => $this->interview->instructions,
            'created_at' => now()->toISOString(),
        ];
    }
}
