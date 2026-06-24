<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * InterviewUpdated
 * 
 * Notification sent when an interview is updated/rescheduled.
 */
class InterviewUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Interview $interview,
        public string $updateType = 'updated'
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
        
        $subject = match($this->updateType) {
            'rescheduled' => 'Interview Rescheduled - ' . $job->title,
            default => 'Interview Updated - ' . $job->title,
        };
        
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line(match($this->updateType) {
                'rescheduled' => 'Your interview has been rescheduled.',
                default => 'Your interview details have been updated.',
            });
            
        $message->line('**Position:** ' . $job->title)
                ->line('**Company:** ' . $employer->company_name)
                ->line('**New Date:** ' . $this->interview->scheduled_at->format('l, F j, Y'))
                ->line('**New Time:** ' . $this->interview->scheduled_at->format('g:i A') . ' ' . $this->interview->timezone)
                ->line('**Duration:** ' . $this->interview->duration_minutes . ' minutes')
                ->line('**Type:** ' . ucfirst(str_replace('_', ' ', $this->interview->interview_type)));
        
        if ($this->interview->location) {
            $message->line('**Location:** ' . $this->interview->location);
        }
        
        if ($this->interview->meeting_link) {
            $message->line('**Meeting Link:** ' . $this->interview->meeting_link);
        }
        
        if ($this->interview->instructions) {
            $message->line('**Instructions:** ' . $this->interview->instructions);
        }
        
        return $message
            ->action('View Updated Details', route('seeker.interviews.show', $this->interview->id))
            ->line('Please confirm your availability for the new time.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $job = $this->interview->jobApplication->jobPosting;
        $employer = $this->interview->employer;
        
        return [
            'type' => 'interview_updated',
            'update_type' => $this->updateType,
            'title' => match($this->updateType) {
                'rescheduled' => 'Interview Rescheduled',
                default => 'Interview Updated',
            },
            'message' => match($this->updateType) {
                'rescheduled' => "Interview rescheduled for {$job->title} at {$employer->company_name}",
                default => "Interview details updated for {$job->title}",
            },
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
            'updated_at' => now()->toISOString(),
        ];
    }
}
