<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Support Ticket Created')
            ->greeting('Hello!')
            ->line('A new support ticket has been created.')
            ->line('Title: '.$this->ticket->title)
            ->line('Priority: '.ucfirst($this->ticket->priority))
            ->action(
                'View Ticket',
                url('/tickets/'.$this->ticket->id)
            )
            ->line('Thank you for using our application!');
    }
}
