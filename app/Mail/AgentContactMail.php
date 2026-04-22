<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subject,
        public string $body,
        public string $senderName,
        public ?string $senderEmail = null,
        public ?string $recipientName = null,
        public ?string $attachmentPath = null,
        public ?string $attachmentName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[E-PNMLS] ' . $this->subject,
            replyTo: $this->senderEmail ? [$this->senderEmail] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.agent-contact',
        );
    }

    public function attachments(): array
    {
        if (!$this->attachmentPath) {
            return [];
        }

        $attachment = Attachment::fromStorageDisk('local', $this->attachmentPath);

        if ($this->attachmentName) {
            $attachment = $attachment->as($this->attachmentName);
        }

        return [$attachment];
    }
}
