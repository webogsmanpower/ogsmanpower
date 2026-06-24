<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VisaDocumentRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $visaStatus,
        public $visaStep,
        public $employer
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $stepName = property_exists($this->visaStep, 'step_name') ? $this->visaStep->step_name : $this->visaStep->name;
        $stepLabel = $this->visaStep->label ?? $this->visaStep->name;

        return [
            'type' => 'visa_document_requested',
            'title' => 'Action Required: Document Request',
            'message' => 'Please upload ' . $stepLabel,
            'visa_status_id' => $this->visaStatus->id,
            'visa_step_id' => $this->visaStep->id,
            'step_name' => $stepName,
            'step_label' => $stepLabel,
            'employer_name' => $this->employer->company_name,
            'action_url' => '/seeker/visa-status',
        ];
    }
}
