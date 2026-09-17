<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipSuspensionContinuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
        public ?DateTimeInterface $eligibleForReviewAt = null,
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
            ->subject(__('IRDI Membership Suspension Continued'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(
                __('Following a formal review, your IRDI membership suspension will remain in effect.')
            )
            ->line(__('Reason for continuation:'))
            ->line($this->reason);

        if ($this->eligibleForReviewAt !== null) {
            $mail
                ->line(
                    __('Your membership will become eligible for another formal review on :date.', [
                        'date' => Carbon::instance($this->eligibleForReviewAt)
                            ->translatedFormat('F j, Y'),
                    ])
                )
                ->line(
                    __('You may request another formal review once that date has been reached.')
                );
        } else {
            $mail->line(
                __('Your suspension is now indefinite and does not currently have a scheduled formal review date.')
            );
        }

        return $mail
            ->line(
                __('Your IRDI membership remains suspended until IRDI formally changes your membership status.')
            )
            ->line(
                __('If you have questions about this decision, please contact IRDI.')
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
