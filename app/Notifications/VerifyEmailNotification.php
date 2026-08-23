<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    use Queueable;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your IRDI Email Address')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Thank you for creating your IRDI account.')
            ->line('Please verify your email address to complete your account setup.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create an IRDI account, you can safely ignore this email.')
            ->salutation('The IRDI Team');
    }
}
