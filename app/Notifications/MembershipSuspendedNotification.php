<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipSuspendedNotification extends Notification
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
     * @param User $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your IRDI Membership Has Been Suspended')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your IRDI membership has been suspended.')
            ->line('While your membership is suspended, your public member profile and member card are unavailable, you cannot send or receive new IRDI member messages, and you cannot request new property owner reviews.')
            ->line('Your IRDI account and existing records remain available.')
            ->action('View Your Account', route('account'))
            ->line('If you have questions about this action, please contact IRDI.');
    }
}
