<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForwardCandidateMail extends Mailable
{
    use SerializesModels;

    public $candidate;

    public function __construct($candidate)
    {
        $this->candidate = $candidate;
    }

    private function getLocalPath($url)
    {
        if (!$url) {
            return null;
        }

        // Remove domain if exists
        $path = preg_replace('#^https?://[^/]+#', '', $url);

        // Convert to server path
        $filePath = public_path($path);

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
    }

    public function build()
    {
        $mail = $this->subject('Candidate Profile: ' . $this->candidate->user->name)
            ->view('emails.forward_candidate');

        // Candidate Photo
        $photo = $this->getLocalPath($this->candidate->photo);
        if ($photo) {
            $mail->attach($photo);
        }

        // CV
        $cv = $this->getLocalPath($this->candidate->cv ?? null);
        if ($cv) {
            $mail->attach($cv);
        }

        // Passport
        $passport = $this->getLocalPath($this->candidate->passport ?? null);
        if ($passport) {
            $mail->attach($passport);
        }

        // Video
        $video = $this->getLocalPath($this->candidate->video ?? null);
        if ($video) {
            $mail->attach($video);
        }

        return $mail;
    }
}