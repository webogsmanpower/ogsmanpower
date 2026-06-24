<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VisaStepUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $oldStep,
        public string $newStep,
        public string $visaType,
        public ?string $note = null,
        public ?string $employerName = null
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'visa_update',
            'title' => 'Visa Status Updated',
            'message' => "Your visa status has been updated from {$this->oldStep} to {$this->newStep}",
            'old_step' => $this->oldStep,
            'new_step' => $this->newStep,
            'visa_type' => $this->visaType,
            'note' => $this->note,
            'employer_name' => $this->employerName,
            'action_url' => '/seeker/visa-status',
        ];
    }
}
