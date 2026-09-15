<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipBannedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
        public bool $permanentlyIneligible,
        public ?string $reinstatementEligibleAt = null,
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
        /** @var User $notifiable */
        $notifiable = $notifiable;

        $mail = (new MailMessage)
            ->subject('IRDI Membership Ban')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your IRDI membership has been terminated and your account has been banned.')
            ->line('Reason:')
            ->line($this->reason);

        if ($this->permanentlyIneligible) {
            $mail->line('You are permanently ineligible for reinstatement.');
        } elseif ($this->reinstatementEligibleAt !== null) {
            $mail->line(
                'You may become eligible to request reinstatement on '.$this->reinstatementEligibleAt.'.'
            );
        }

        return $mail
            ->line('This action concludes the current Administrative Review.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
