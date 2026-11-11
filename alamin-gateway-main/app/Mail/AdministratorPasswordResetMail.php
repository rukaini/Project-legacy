<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdministratorPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $resetUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your ELIT Portal Administrator password');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.administrator-password-reset');
    }
}
