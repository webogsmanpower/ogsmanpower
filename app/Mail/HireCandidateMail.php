<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HireCandidateMail extends Mailable
{
    use Queueable, SerializesModels;
    public $candidate;
    public $message;

    /**
     * Create a new message instance.
     *
     * @param $candidate
     * @param $message
     */
    public function __construct($candidate, $message)
    {
        $this->candidate = $candidate;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.hire_candidate')
            ->subject('Someone Wants to Hire You!')
            ->with([
                'candidateName' => $this->candidate->user->name,
                'messageBody' => $this->message,
            ]);
    }
}
