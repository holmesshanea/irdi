<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMemberMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->sender->memberProfile?->profile_name
            ?? $this->message->sender->name;

        return (new MailMessage)
            ->subject('You have a new message on IRDI')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($senderName . ' sent you a new private message on IRDI.')
            ->line('Sign in to IRDI to read and reply.')
            ->action('View Messages', route('messages.index'))
            ->line('Your email address has not been shared with the sender.');
    }
}
