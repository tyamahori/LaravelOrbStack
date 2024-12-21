<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppHealthCheck extends Mailable
{
    use Queueable;
    use SerializesModels;
    /**
     * Create a new message instance.
     */
    public function __construct()
    {

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'App Health Check',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.app.healthcheck',
            with: [
                'appName' => 'LaravelOrbStack',
            ]
        );
    }
}
