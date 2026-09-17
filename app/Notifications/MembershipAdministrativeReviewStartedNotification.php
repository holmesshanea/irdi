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
            ->subject(__('IRDI Membership Administrative Review'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your IRDI membership has been placed under Administrative Review.'))
            ->line(__('While the review is active, your membership is not considered to be in Good Standing.'))
            ->line(__('Reason:'))
            ->line($this->reason)
            ->line(__('Administrative Review does not by itself suspend or terminate your membership. IRDI staff will review the matter and determine whether any further accountability action is appropriate.'))
            ->action(__('View Your Account'), url('/account'))
            ->line(__('You will be notified when the Administrative Review is resolved or if further action is taken.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
