<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessagingRestoredNotification extends Notification
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
            ->subject('Your IRDI Messaging Access Has Been Restored')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your IRDI member messaging access has been restored.')
            ->line('You can once again send and receive new messages from other eligible IRDI members.')
            ->action('View Messages', route('messages.index'))
            ->line('Thank you for being part of IRDI.');
    }
}
