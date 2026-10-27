<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionStatusChanged extends Mailable
{
    use SerializesModels;

    public function __construct(public Submission $submission)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update Status Pengajuan UMKM - ' . $this->submission->registration_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.submission-status',
        );
    }
}
