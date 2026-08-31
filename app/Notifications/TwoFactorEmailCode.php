<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers a short-lived sign-in code to a guest's verified email address.
 */
class TwoFactorEmailCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * Send this notification through the mail channel only.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the email containing the temporary verification code.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Filament Hotel verification code')
            ->greeting('Verify your sign-in')
            ->line("Use {$this->code} to finish signing in to your Filament Hotel guest account.")
            ->line('This code expires in 10 minutes and can be used only once.')
            ->line('If you did not try to sign in, you can ignore this email.');
    }

    /**
     * No database notification is stored for a one-time login code.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
