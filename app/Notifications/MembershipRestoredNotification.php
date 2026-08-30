<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRestoredNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your IRDI Membership Has Been Restored')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your IRDI membership has been restored and is active again.')
            ->line('Your public member profile, member card, member messaging, property owner review requests, and other active-member privileges are available again.')
            ->action('View Your Account', route('account'))
            ->line('Thank you for being part of IRDI.');
    }
}
