<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipReinstatementDeniedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
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

        return (new MailMessage)
            ->subject('IRDI Membership Reinstatement Petition Decision')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your petition for reinstatement to IRDI membership has been reviewed.')
            ->line('Your reinstatement petition has been denied, and your membership ban remains active.')
            ->line('Reason:')
            ->line($this->reason)
            ->action('View Your Account', url('/account'))
            ->line('Any future eligibility for reinstatement remains subject to the terms of your active membership ban and IRDI membership policies.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
