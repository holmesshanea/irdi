<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessagingRestrictedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?CarbonInterface $expiresAt = null,
    ) {}

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
        $mail = (new MailMessage)
            ->subject('Your IRDI Messaging Access Has Been Restricted')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your ability to send and receive new IRDI member messages has been restricted.')
            ->line('You can still access your account and existing message history.');

        if ($this->expiresAt) {
            $mail->line(
                'This restriction is temporary and is scheduled to expire on '
                .$this->expiresAt->format('F j, Y \a\t g:i A')
                .'.'
            );
        } else {
            $mail->line(
                'This restriction will remain in effect until an administrator restores your messaging access.'
            );
        }

        return $mail
            ->action('View Your Account', route('account'))
            ->line('If you have questions about this action, please contact IRDI.');
    }
}
