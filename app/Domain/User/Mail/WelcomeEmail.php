<?php

declare(strict_types=1);

namespace App\Domain\User\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to E-Commerce! Please verify your email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'verificationUrl' => $this->generateVerificationUrl(),
            ],
        );
    }

    /**
     * Generate email verification URL.
     */
    private function generateVerificationUrl(): string
    {
        return url('/api/v1/auth/verify-email/' . $this->user->verification_token);
    }
}
