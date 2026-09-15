<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipProbationResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public bool $inGoodStanding,
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
        $mail = (new MailMessage)
            ->subject('IRDI Membership Probation Resolved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your IRDI Membership Probation has been reviewed and is now resolved.')
            ->line('The probation is no longer an active membership enforcement action.');

        if ($this->inGoodStanding) {
            $mail->line('Your membership is now considered to be in Good Standing.');
        } else {
            $mail->line(
                'Another active membership accountability action remains in effect, so your membership is not currently considered to be in Good Standing.'
            );
        }

        return $mail
            ->action('View Your Account', url('/account'))
            ->line('Thank you for your continued commitment to IRDI and responsible metal detecting.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
