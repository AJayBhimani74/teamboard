<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->invitation->workspace->name;

        return (new MailMessage)
            ->subject("You have been invited to {$workspace}")
            ->line("{$this->invitation->inviter->name} invited you to join {$workspace} on Teamboard.")
            ->action('Accept invitation', route('invitations.accept', $this->invitation->token))
            ->line('The link works for 7 days.');
    }
}
