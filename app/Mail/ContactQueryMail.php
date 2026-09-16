<?php

namespace App\Mail;

use App\Models\ContactQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactQueryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactQuery $query) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New website query · '.$this->query->intentLabel(),
            replyTo: [
                new Address($this->query->email, $this->query->name ?: $this->query->email),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-query');
    }
}
