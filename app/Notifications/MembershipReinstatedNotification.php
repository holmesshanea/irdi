<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipReinstatedNotification extends Notification implements ShouldQueue
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
            ->subject('IRDI Membership Reinstated')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your IRDI membership has been reinstated.')
            ->line('Your membership ban is no longer active, and your membership status has been restored to active.')
            ->line('Reason:')
            ->line($this->reason)
            ->action('View Your Account', url('/account'))
            ->line('Thank you for your continued commitment to IRDI standards and responsible detecting.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
