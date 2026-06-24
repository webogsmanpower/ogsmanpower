<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class VisaDocumentRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $documents;
    protected $note;
    protected $visaType;
    protected $employerName;

    public function __construct(array $documents, ?string $note, string $visaType, string $employerName)
    {
        $this->documents = $documents;
        $this->note = $note;
        $this->visaType = $visaType;
        $this->employerName = $employerName;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $documentList = implode("\n• ", array_column($this->documents, 'label'));
        
        return (new MailMessage)
            ->subject('Document Request for ' . $this->visaType)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->employerName . ' has requested additional documents for your ' . $this->visaType . ' application.')
            ->line('Required documents:')
            ->line('• ' . $documentList)
            ->when($this->note, function ($message) {
                return $message->line('Note: ' . $this->note);
            })
            ->action('Upload Documents', url('/seeker/visa-status'))
            ->line('Please log in to your account and upload the requested documents as soon as possible.')
            ->salutation('Thank you for your cooperation.');
    }

    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'title' => 'Documents Requested',
            'description' => $this->employerName . ' requested ' . count($this->documents) . ' document(s) for your ' . $this->visaType,
            'documents' => $this->documents,
            'note' => $this->note,
            'visa_type' => $this->visaType,
            'employer_name' => $this->employerName,
            'type' => 'visa_document_request',
        ]);
    }
}
