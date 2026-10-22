<?php

namespace App\Mail;

use App\Models\Notifikasi;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NotifikasiMail extends Mailable
{
    public function __construct(public Notifikasi $notifikasi) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Intellisys Talent Hub] Pemberitahuan');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.notifikasi');
    }
}
