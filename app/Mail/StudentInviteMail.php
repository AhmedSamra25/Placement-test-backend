<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $inviteLink
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been invited to take a Language Placement Test!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student_invite',
        );
    }
}
