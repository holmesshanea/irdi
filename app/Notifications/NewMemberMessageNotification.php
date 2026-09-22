<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
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

    public function toMail(User $notifiable): MailMessage
    {
        $sender = $this->message->sender;

        $senderName = $sender->memberProfile
            ? $sender->memberProfile->publicName()
            : $sender->name;

        return (new MailMessage)
            ->subject(__('You have a new message on IRDI'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':name sent you a new private message on IRDI.', ['name' => $senderName]))
            ->line(__('Sign in to IRDI to read and reply.'))
            ->action(__('View Messages'), route('messages.index'))
            ->line(__('Your email address has not been shared with the sender.'));
    }
}
