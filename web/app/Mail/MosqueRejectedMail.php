<?php

namespace App\Mail;

use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MosqueRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mosque $mosque,
        public Carbon $rejectionDate,
        public string $rejectionReason,
    ) {}

    /**
     * Build the message.
     *
     * Requirements: 4.7, 4.8
     */
    public function build(): static
    {
        return $this->subject('Mosque Registration Status')
            ->view('emails.mosque-rejected');
    }
}
