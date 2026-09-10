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
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your IRDI Membership Has Been Suspended'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your IRDI membership has been suspended.'))
            ->line(__('While your membership is suspended, your public member profile and member card are unavailable, you cannot send or receive new IRDI member messages, and you cannot request new property owner reviews.'))
            ->line(__('Your IRDI account and existing records remain available.'))
            ->action(__('View Your Account'), route('account'))
            ->line(__('If you have questions about this action, please contact IRDI.'));
    }
}
