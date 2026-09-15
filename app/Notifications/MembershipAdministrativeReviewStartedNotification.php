<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipAdministrativeReviewStartedNotification extends Notification implements ShouldQueue
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
        $notifiable = $notifiable;

        return (new MailMessage)
            ->subject('IRDI Membership Administrative Review')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your IRDI membership has been placed under Administrative Review.')
            ->line('While the review is active, your membership is not considered to be in Good Standing.')
            ->line('Reason:')
            ->line($this->reason)
            ->line('Administrative Review does not by itself suspend or terminate your membership. IRDI staff will review the matter and determine whether any further accountability action is appropriate.')
            ->action('View Your Account', url('/account'))
            ->line('You will be notified when the Administrative Review is resolved or if further action is taken.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
