<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $token,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes = config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire'
        );

        return (new MailMessage)
            ->subject(__('Reset Your IRDI Password'))
            ->greeting(__('Hello :name!', ['name' => $notifiable->name]))
            ->line(__('We received a request to reset the password for your IRDI account.'))
            ->line(__('Click the button below to choose a new password.'))
            ->action(__('Reset Password'), $url)
            ->line(__('This password reset link will expire in :minutes minutes.', ['minutes' => $minutes]))
            ->line(__('If you did not request a password reset, you can safely ignore this email. No changes will be made to your account.'))
            ->salutation(__('The IRDI Team'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
