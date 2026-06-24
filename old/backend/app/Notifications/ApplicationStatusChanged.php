<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ApplicationStatusChanged Notification
 * 
 * Notifies seekers when their application status changes.
 */
class ApplicationStatusChanged extends Notification
{
    use Queueable;

    protected JobApplication $application;
    protected string $previousStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(JobApplication $application, string $previousStatus = 'applied')
    {
        $this->application = $application;
        $this->previousStatus = $previousStatus;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $jobTitle = $this->application->jobPosting?->title ?? 'Unknown Job';
        $companyName = $this->application->employer?->company_name ?? 
                       $this->application->jobPosting?->employer?->company_name ?? 'Unknown Company';
        $newStatus = $this->application->status;
        $statusLabel = $this->application->status_label;

        return [
            'type' => 'application_status_changed',
            'application_id' => $this->application->id,
            'job_posting_id' => $this->application->job_posting_id,
            'job_title' => $jobTitle,
            'company_name' => $companyName,
            'previous_status' => $this->previousStatus,
            'new_status' => $newStatus,
            'status_label' => $statusLabel,
            'message' => $this->getMessage($statusLabel, $jobTitle, $companyName),
            'title' => $this->getTitle($newStatus),
            'icon' => $this->getIcon($newStatus),
            'priority' => $this->getPriority($newStatus),
            'action_url' => "/seeker/applications/{$this->application->id}",
            'changed_at' => $this->application->status_changed_at?->toIso8601String(),
        ];
    }

    /**
     * Get notification message based on status.
     */
    protected function getMessage(string $statusLabel, string $jobTitle, string $companyName): string
    {
        return match($this->application->status) {
            'reviewed' => "Your application for {$jobTitle} at {$companyName} is being reviewed.",
            'shortlisted' => "Great news! You've been shortlisted for {$jobTitle} at {$companyName}.",
            'interview_scheduled' => "Interview scheduled for {$jobTitle} at {$companyName}. Check details.",
            'interviewed' => "Your interview for {$jobTitle} at {$companyName} has been completed.",
            'contract_sent' => "Contract sent for {$jobTitle} at {$companyName}. Please review and sign.",
            'hired' => "Congratulations! You've been hired for {$jobTitle} at {$companyName}!",
            'rejected' => "Your application for {$jobTitle} at {$companyName} was not selected.",
            default => "Your application for {$jobTitle} has been updated to: {$statusLabel}.",
        };
    }

    /**
     * Get notification title based on status.
     */
    protected function getTitle(string $status): string
    {
        return match($status) {
            'reviewed' => 'Application Under Review',
            'shortlisted' => 'You\'ve Been Shortlisted!',
            'interview_scheduled' => 'Interview Scheduled',
            'interviewed' => 'Interview Completed',
            'contract_sent' => 'Contract Ready for Review',
            'hired' => 'Congratulations - You\'re Hired!',
            'rejected' => 'Application Update',
            default => 'Application Status Updated',
        };
    }

    /**
     * Get icon for notification.
     */
    protected function getIcon(string $status): string
    {
        return match($status) {
            'reviewed' => 'eye',
            'shortlisted' => 'star',
            'interview_scheduled' => 'calendar',
            'interviewed' => 'check-circle',
            'contract_sent' => 'file-text',
            'hired' => 'award',
            'rejected' => 'x-circle',
            default => 'bell',
        };
    }

    /**
     * Get priority for notification.
     */
    protected function getPriority(string $status): string
    {
        return match($status) {
            'interview_scheduled', 'contract_sent', 'hired' => 'high',
            'shortlisted', 'interviewed' => 'medium',
            default => 'low',
        };
    }
}
