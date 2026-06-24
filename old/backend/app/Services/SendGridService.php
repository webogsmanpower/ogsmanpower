<?php

namespace App\Services;

use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\TypeException;

class SendGridService
{
    private SendGrid $sendGrid;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->sendGrid = new SendGrid(config('services.sendgrid.api_key'));
        // Use a test email that should be verified in SendGrid
        $this->fromEmail = config('mail.from.address', 'test@ogsapp.com');
        $this->fromName = config('mail.from.name', 'OGS App');
    }

    /**
     * Send an email using SendGrid
     *
     * @param string $to
     * @param string $toName
     * @param string $subject
     * @param string $content
     * @param string $contentType
     * @return array
     */
    public function sendEmail(string $to, string $toName, string $subject, string $content, string $contentType = 'text/html'): array
    {
        try {
            \Log::info("SendGrid: Attempting to send email to {$to}");
            
            $email = new Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($subject);
            $email->addTo($to, $toName);
            $email->addContent($contentType, $content);

            \Log::info("SendGrid: Email object created, sending...");
            $response = $this->sendGrid->send($email);
            
            \Log::info("SendGrid: Response received", [
                'status_code' => $response->statusCode(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            return [
                'success' => $response->statusCode() >= 200 && $response->statusCode() < 300,
                'status_code' => $response->statusCode(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ];
        } catch (TypeException $e) {
            \Log::error('SendGrid TypeException: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Invalid email type: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            \Log::error('SendGrid Exception: ' . $e->getMessage());
            \Log::error('SendGrid Exception trace: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => 'SendGrid error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a welcome email
     */
    public function sendWelcomeEmail(string $to, string $toName): array
    {
        $subject = 'Welcome to OGS App';
        $content = $this->getWelcomeEmailTemplate($toName);
        
        return $this->sendEmail($to, $toName, $subject, $content);
    }

    /**
     * Send a password reset email
     */
    public function sendPasswordResetEmail(string $to, string $toName, string $resetLink): array
    {
        $subject = 'Reset Your Password';
        $content = $this->getPasswordResetTemplate($toName, $resetLink);
        
        return $this->sendEmail($to, $toName, $subject, $content);
    }

    /**
     * Send a contract notification email
     */
    public function sendContractNotification(string $to, string $toName, string $contractLink): array
    {
        $subject = 'New Contract Available';
        $content = $this->getContractNotificationTemplate($toName, $contractLink);
        
        return $this->sendEmail($to, $toName, $subject, $content);
    }

    /**
     * Get welcome email template
     */
    private function getWelcomeEmailTemplate(string $name): string
    {
        return "
        <html>
        <body>
            <h2>Welcome to OGS App, {$name}!</h2>
            <p>Thank you for joining our platform. We're excited to have you on board.</p>
            <p>You can now:</p>
            <ul>
                <li>Create your profile</li>
                <li>Browse job opportunities</li>
                <li>Connect with employers</li>
            </ul>
            <p>If you have any questions, feel free to contact our support team.</p>
            <p>Best regards,<br>The OGS App Team</p>
        </body>
        </html>";
    }

    /**
     * Get password reset email template
     */
    private function getPasswordResetTemplate(string $name, string $resetLink): string
    {
        return "
        <html>
        <body>
            <h2>Password Reset Request</h2>
            <p>Hello {$name},</p>
            <p>You requested to reset your password. Click the link below to reset it:</p>
            <p><a href='{$resetLink}'>Reset Password</a></p>
            <p>This link will expire in 60 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
            <p>Best regards,<br>The OGS App Team</p>
        </body>
        </html>";
    }

    /**
     * Get contract notification email template
     */
    private function getContractNotificationTemplate(string $name, string $contractLink): string
    {
        return "
        <html>
        <body>
            <h2>New Contract Available</h2>
            <p>Hello {$name},</p>
            <p>A new contract is available for your review. Click the link below to view and sign:</p>
            <p><a href='{$contractLink}'>View Contract</a></p>
            <p>Please review the contract carefully and contact us if you have any questions.</p>
            <p>Best regards,<br>The OGS App Team</p>
        </body>
        </html>";
    }
}
