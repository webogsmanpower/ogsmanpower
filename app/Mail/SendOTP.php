<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOTP extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $otp;
    public $username;
    public function __construct($otp, $username)
    {
        $this->otp = $otp;
        $this->username = $username;
    }

    public function build()

    {
        return $this->subject('Your OTP Code')
                    ->view('mails.send_otp')
                    ->with([
                        'otp' => $this->otp,
                        'username' => $this->username,
                    ]);
    }

}
