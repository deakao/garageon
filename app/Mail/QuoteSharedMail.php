<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Orçamento #'.str_pad((string) $this->quote->id, 4, '0', STR_PAD_LEFT).' - '.$this->quote->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotes.shared',
        );
    }
}
