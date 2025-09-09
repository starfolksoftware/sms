<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public User $user)
    {
        // You can configure queueing later if desired
    }

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
        $appName = config('app.name');
        // Use a generic accept-invite URL with the token. Frontend/route can be wired later.
        $acceptUrl = url('/invitation/accept?token=' . $this->user->invitation_token);

        return (new MailMessage)
            ->subject("You're invited to {$appName}")
            ->greeting('Hello ' . ($this->user->name ?: ''))
            ->line('You have been invited to join ' . $appName . '.')
            ->action('Accept Invitation', $acceptUrl)
            ->line('If you did not expect this, you can safely ignore this email.');
    }
}
