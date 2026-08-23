<?php

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Team $team,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You have been invited to join a team');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.team-invitation');
    }
}
