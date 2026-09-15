<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipWarningResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

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
            ->subject('IRDI Membership Warning Resolved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your Formal Membership Warning has been reviewed and is now resolved.')
            ->line('The warning is no longer an active membership enforcement action.')
            ->line('Your IRDI membership remains active.')
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
