<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\QuoteFunnelAutomation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteFunnelAutomationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public QuoteFunnelAutomation $automation,
        public string $renderedBody,
        public string $renderedSubject,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotes.funnel-automation',
        );
    }
}
