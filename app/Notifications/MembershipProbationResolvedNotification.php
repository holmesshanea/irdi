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
            ->subject(__('IRDI Membership Probation Resolved'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your IRDI Membership Probation has been reviewed and is now resolved.'))
            ->line(__('The probation is no longer an active membership enforcement action.'));

        if ($this->inGoodStanding) {
            $mail->line(
                __('Your membership is now considered to be in Good Standing.')
            );
        } else {
            $mail->line(
                __('Another active membership accountability action remains in effect, so your membership is not currently considered to be in Good Standing.')
            );
        }

        return $mail
            ->action(__('View Your Account'), url('/account'))
            ->line(__('Thank you for your continued commitment to IRDI and responsible metal detecting.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
