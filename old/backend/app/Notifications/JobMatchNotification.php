<?php

namespace App\Notifications;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class JobMatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private JobPosting $job,
        private $seeker
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Job Match!',
            'message' => "A new job matching your preferences has been posted: {$this->job->title}",
            'job_id' => $this->job->id,
            'job_title' => $this->job->title,
            'job_location' => $this->job->location,
            'job_company' => $this->job->company_name,
            'salary_min' => $this->job->salary_min,
            'salary_max' => $this->job->salary_max,
            'application_deadline' => $this->job->application_deadline,
            'type' => 'job_match',
            'action_url' => '/jobs/' . $this->job->id,
            'icon' => 'briefcase',
            'priority' => 'medium'
        ];
    }
}
