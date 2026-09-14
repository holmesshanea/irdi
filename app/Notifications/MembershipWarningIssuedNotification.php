<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipWarningIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $reason,
        public ?string $requirements = null,
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
        $message = (new MailMessage)
            ->subject(__('IRDI Formal Membership Warning'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('IRDI has issued a formal membership warning regarding your account.'))
            ->line(__('Reason: :reason', ['reason' => $this->reason]));

        if ($this->requirements !== null && $this->requirements !== '') {
            $message->line(__('Corrective action or training requested: :requirements', [
                'requirements' => $this->requirements,
            ]));
        }

        return $message
            ->line(__('A formal warning does not suspend your membership or remove your Member in Good Standing status.'))
            ->action(__('View Your Account'), route('account'))
            ->line(__('If you have questions about this warning, please contact IRDI.'));
    }
}
