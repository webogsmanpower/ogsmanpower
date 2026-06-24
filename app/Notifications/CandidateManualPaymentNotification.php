<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidateManualPaymentNotification extends Notification
{
    use Queueable;
    public $payment;
    /**
     * Create a new notification instance.
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('Manual Payment Initiated'))
            ->greeting(__('Hello ') . $notifiable->name)
            ->line(__('Your manual payment for the plan ":plan" has been initiated.', ['plan' => $this->payment->plan->name]))
            ->line(__('Payment Amount: ') . $this->payment->amount . ' ' . config('app.currency'))
            ->line(__('Payment Duration: ') . $this->payment->duration . __(' months'))
            ->line(__('Please follow the manual payment instructions to complete the process.'))
            ->action(__('View Payment Details'), route('payments.details', $this->payment->id))
            ->line(__('Thank you for using our service!'));
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'payment_id' => $this->payment->id,
            'plan_name' => $this->payment->plan->name,
            'amount' => $this->payment->amount,
            'duration' => $this->payment->duration,
        ];
    }
}
