<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipAdministrativeReviewClosedNotification extends Notification implements ShouldQueue
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
        $notifiable = $notifiable;

        return (new MailMessage)
            ->subject('IRDI Membership Administrative Review Closed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The Administrative Review of your IRDI membership has been completed.')
            ->line('The review has been closed with no further membership action.')
            ->line('Your membership remains active and is no longer under Administrative Review.')
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
