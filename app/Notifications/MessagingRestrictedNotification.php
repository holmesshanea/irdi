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
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('Your IRDI Messaging Access Has Been Restricted'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your ability to send and receive new IRDI member messages has been restricted.'))
            ->line(__('You can still access your account and existing message history.'));

        if ($this->expiresAt) {
            $mail->line(__('This restriction is temporary and is scheduled to expire on :date.', [
                'date' => __(':date at :time', [
                    'date' => $this->expiresAt->translatedFormat('F j, Y'),
                    'time' => $this->expiresAt->translatedFormat('g:i A'),
                ]),
            ]));
        } else {
            $mail->line(
                __('This restriction will remain in effect until an administrator restores your messaging access.')
            );
        }

        return $mail
            ->action(__('View Your Account'), route('account'))
            ->line(__('If you have questions about this action, please contact IRDI.'));
    }
}
